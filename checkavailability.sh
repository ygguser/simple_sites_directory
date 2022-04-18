#!/bin/bash

function checkURL() { #$1 - URL, $2 - method (1 - HEAD else - GET)
    if [ "$2" = 1 ]
    then
        if ! curl --connect-timeout 20 --max-time 120 --silent --head --location --insecure --include "$1" 2>&1 | grep -m 1 -q "200\ \|203\ \|206\ \|501\ \|301\ "
        then
            echo "$1" 
        fi
    else
        if ! curl --connect-timeout 20 --max-time 120 --silent --location --insecure --include --user-agent 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:98.0) Gecko/20100101 Firefox/98.0' "$1" 2>&1 | grep -m 1 -q "200\ \|203\ \|206\ \|501\ \|301\ "
        then
            echo "$1"
        fi
    fi
}

export -f checkURL # to pass to parallel

echo 'Checking availability...'

DIR=$(dirname $0)
PATHTODB="$DIR/database.db"

if [ ! -f "$PATHTODB" ]; then echo 'Database file not found!'; exit 1; fi

DATATOCHECK=$(sqlite3 "$PATHTODB" 'select URL from Sites;')
#first make HEAD requests, then GET (Not all services respond correctly to the HEAD request (for ex: icecast))
UNAVAILABLE_HEAD_REQ=$(echo "$DATATOCHECK" | parallel -j 16 checkURL {1} 1)
if [ ! -z "$UNAVAILABLE_HEAD_REQ" ]
then
    UNAVAILABLE_GET_REQ=$(echo "$UNAVAILABLE_HEAD_REQ" | parallel -j 16 checkURL {1} 0)
else
    UNAVAILABLE_GET_REQ="$UNAVAILABLE_HEAD_REQ"
fi

if [ ! -z "$UNAVAILABLE_GET_REQ" ] # Unavailable sites are present
then
    echo 'These sites are not available:'
    echo "$UNAVAILABLE_GET_REQ"
    echo -n 'Updating data in the database...'

    UNAV_LISTWITHCOMMAS=$(echo "$UNAVAILABLE_GET_REQ" | sed "s/\(.*\)/'\1'/g" | sed -z 's/\n/,/g;s/,$/\n/') #the first sed encloses the string in single quotes, the second one - replaces the line breaks with commas

    COMMANDS="PRAGMA foreign_keys = ON; BEGIN TRANSACTION;"

    #For unavailable sites, set the sign of unavailability and incrementing NumberOfCheks, NumberOfUnavailability
    COMMANDS="$COMMANDS UPDATE Sites SET Available = 0, NumberOfChecks = NumberOfChecks + 1, NumberOfUnavailability = NumberOfUnavailability + 1 WHERE URL IN ($UNAV_LISTWITHCOMMAS);"

    #Delete sites that are unavailable for more than 30 days
    COMMANDS="$COMMANDS DELETE FROM Sites WHERE julianday('now') - julianday(AvailabilityDate) > 30;"

    #For available sites, set the availability flag, update the availability date and incrementing the values of NumberOfCheks
    COMMANDS="$COMMANDS UPDATE Sites SET Available = 1, NumberOfChecks = NumberOfChecks + 1, AvailabilityDate = \"$(date +"%F %H:%M:%S")\"  WHERE URL NOT IN ($UNAV_LISTWITHCOMMAS);"

    COMMANDS="$COMMANDS COMMIT;"

    echo "$COMMANDS" | sqlite3 "$PATHTODB" -batch
else # There are no unavailable sites
    #For available sites, set the availability flag, update the availability date and incrementing the values of NumberOfCheks
    sqlite3 "$PATHTODB" "UPDATE Sites SET Available = 1, NumberOfChecks = NumberOfChecks + 1, AvailabilityDate = \"$(date +"%F %H:%M:%S")\";"
fi

echo '   Done.'
echo 'Checking availability done.'

echo 'Generating HTML-pages...'
php -f "$DIR/php-backend/ygg_generateHTML.php"
echo 'Done.'
