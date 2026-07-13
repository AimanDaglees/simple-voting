# Functional and API testing

Set the project URL:

```bash
BASE_URL="https://simple-voting.lndo.site"
```

API credentials:

```text
api_voter / api_voter
```

## List questions

```bash
curl -i           -u api_voter:api_voter           -H "Accept: application/json"           "$BASE_URL/api/v1/voting/questions"
```

Expected: `200`.

## Get a question

```bash
QUESTION_ID=1

curl -i           -u api_voter:api_voter           -H "Accept: application/json"           "$BASE_URL/api/v1/voting/questions/$QUESTION_ID"
```

Expected: `200`.

## Submit a vote

```bash
OPTION_ID=1

curl -i           -u api_voter:api_voter           -X POST           -H "Accept: application/json"           -H "Content-Type: application/json"           -d "{\"option_id\":$OPTION_ID}"           "$BASE_URL/api/v1/voting/questions/$QUESTION_ID/votes"
```

Expected: `201`. Repeating the request must return `409`.

## Retrieve results

```bash
curl -i           -u api_voter:api_voter           -H "Accept: application/json"           "$BASE_URL/api/v1/voting/questions/$QUESTION_ID/results"
```

Expected: `200` when results are visible.

## Unauthenticated request

```bash
curl -i "$BASE_URL/api/v1/voting/questions"
```

Expected: `401`.

## Global shutdown

```bash
lando drush config:set simple_voting.settings voting_enabled 0 -y
lando drush cr
```

The API must return `503`, and `/voting` must report that voting is
disabled.

Re-enable voting:

```bash
lando drush config:set simple_voting.settings voting_enabled 1 -y
lando drush cr
```

## Concurrent vote test

```bash
QUESTION_ID=3
OPTION_ID=7
rm -f /tmp/simple-voting-statuses.txt

for i in $(seq 1 10); do
  (
    curl -sS               -o /dev/null               -w "%{http_code}\n"               -u concurrent_voter:concurrent_voter               -X POST               -H "Accept: application/json"               -H "Content-Type: application/json"               -d "{\"option_id\":$OPTION_ID}"               "$BASE_URL/api/v1/voting/questions/$QUESTION_ID/votes"
  ) >> /tmp/simple-voting-statuses.txt &
done

wait
sort /tmp/simple-voting-statuses.txt | uniq -c
```

Expected:

```text
1 201
9 409
```

Verify the stored vote:

```bash
lando mysql -e "
SELECT u.name, COUNT(*) AS vote_count
FROM simple_voting_vote v
INNER JOIN users_field_data u ON u.uid = v.uid
WHERE u.name = 'concurrent_voter'
GROUP BY u.name;
"
```
