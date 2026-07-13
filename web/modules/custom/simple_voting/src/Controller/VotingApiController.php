<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\simple_voting\Exception\DuplicateVoteException;
use Drupal\simple_voting\Exception\InvalidVoteException;
use Drupal\simple_voting\Exception\VotingDisabledException;
use Drupal\simple_voting\Service\QuestionSerializer;
use Drupal\simple_voting\Service\ResultManager;
use Drupal\simple_voting\Service\VoteManager;
use Drupal\simple_voting\VotingQuestionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manually implemented external voting API.
 */
final class VotingApiController implements ContainerInjectionInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly VoteManager $voteManager,
    private readonly ResultManager $resultManager,
    private readonly QuestionSerializer $serializer,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Factory.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get(VoteManager::class),
      $container->get(ResultManager::class),
      $container->get(QuestionSerializer::class),
      $container->get('current_user'),
      $container->get('logger.channel.simple_voting'),
    );
  }

  /**
   * Lists active questions with pagination.
   */
  public function questions(Request $request): JsonResponse {
    if (!$this->currentUser->hasPermission('access simple voting')) {
      return $this->error('access_denied', 'Access to voting questions is denied.', 403);
    }

    if (!$this->voteManager->isEnabled()) {
      return $this->error('voting_disabled', 'Voting is currently disabled.', 503);
    }

    $configured_limit = (int) ($this->configFactory
      ->get('simple_voting.settings')
      ->get('api_page_limit') ?? 25);

    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(100, max(1, (int) $request->query->get('limit', $configured_limit)));

    $storage = $this->entityTypeManager->getStorage('voting_question');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->sort('created', 'DESC');

    $count_query = clone $query;
    $total = (int) $count_query->count()->execute();

    $ids = $query
      ->range($page * $limit, $limit)
      ->execute();

    $items = [];
    foreach ($storage->loadMultiple($ids) as $question) {
      if ($question instanceof VotingQuestionInterface) {
        $items[] = $this->serializer->serialize($question, FALSE);
      }
    }

    return new JsonResponse([
      'data' => $items,
      'meta' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
      ],
    ]);
  }

  /**
   * Returns one active question and its options.
   */
  public function question(VotingQuestionInterface $voting_question): JsonResponse {
    if (!$this->currentUser->hasPermission('access simple voting')) {
      return $this->error('access_denied', 'Access to voting questions is denied.', 403);
    }

    if (!$this->voteManager->isEnabled()) {
      return $this->error('voting_disabled', 'Voting is currently disabled.', 503);
    }

    if (!$voting_question->isPublished()) {
      return $this->error('question_not_found', 'The question was not found.', 404);
    }

    $data = $this->serializer->serialize($voting_question);
    $data['has_voted'] = $this->voteManager->hasVoted((int) $voting_question->id());
    $data['selected_option_id'] = $this->voteManager->getSelectedOptionId((int) $voting_question->id());

    return new JsonResponse(['data' => $data]);
  }

  /**
   * Records a vote.
   */
  public function vote(Request $request, VotingQuestionInterface $voting_question): JsonResponse {
    if (!$this->currentUser->hasPermission('cast simple voting votes')) {
      return $this->error('access_denied', 'This account cannot cast votes.', 403);
    }

    if (!$voting_question->isPublished()) {
      return $this->error('question_not_found', 'The question was not found.', 404);
    }

    try {
      $payload = json_decode($request->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      return $this->error('invalid_json', 'The request body must contain valid JSON.', 400);
    }

    if (!is_array($payload) || !isset($payload['option_id']) || !is_numeric($payload['option_id'])) {
      return $this->error('invalid_payload', 'A numeric option_id is required.', 400);
    }

    try {
      $vote_id = $this->voteManager->castVote(
        $voting_question,
        (int) $payload['option_id'],
        'api'
      );
    }
    catch (VotingDisabledException $exception) {
      return $this->error('voting_disabled', $exception->getMessage(), 503);
    }
    catch (DuplicateVoteException $exception) {
      return $this->error('duplicate_vote', $exception->getMessage(), 409);
    }
    catch (InvalidVoteException $exception) {
      return $this->error('invalid_vote', $exception->getMessage(), 422);
    }
    catch (\Throwable $exception) {
      $this->logger->error('Unexpected API vote failure: {message}', ['message' => $exception->getMessage()]);
      return $this->error('internal_error', 'The vote could not be processed.', 500);
    }

    $response = [
      'data' => [
        'vote_id' => $vote_id,
        'question_id' => (int) $voting_question->id(),
        'option_id' => (int) $payload['option_id'],
        'message' => 'Vote recorded.',
      ],
    ];

    if ($voting_question->showsResults()) {
      $response['data']['results'] = $this->resultManager->getResults($voting_question);
    }

    return new JsonResponse($response, 201);
  }

  /**
   * Returns results when configured and after this user has voted.
   */
  public function results(VotingQuestionInterface $voting_question): JsonResponse {
    $is_admin = $this->currentUser->hasPermission('administer simple voting');
    if (!$is_admin && !$this->currentUser->hasPermission('view simple voting results')) {
      return $this->error('access_denied', 'This account cannot view voting results.', 403);
    }

    if (!$this->voteManager->isEnabled()) {
      return $this->error('voting_disabled', 'Voting is currently disabled.', 503);
    }

    if (!$voting_question->isPublished()) {
      return $this->error('question_not_found', 'The question was not found.', 404);
    }

    if (!$voting_question->showsResults() && !$is_admin) {
      return $this->error('results_hidden', 'Results are hidden for this question.', 403);
    }

    if (!$is_admin && !$this->voteManager->hasVoted((int) $voting_question->id())) {
      return $this->error('vote_required', 'Results are available only after voting.', 403);
    }

    return new JsonResponse([
      'data' => $this->resultManager->getResults($voting_question),
    ]);
  }

  /**
   * Creates a consistent error envelope.
   */
  private function error(string $code, string $message, int $status): JsonResponse {
    return new JsonResponse([
      'error' => [
        'code' => $code,
        'message' => $message,
        'status' => $status,
      ],
    ], $status);
  }

}
