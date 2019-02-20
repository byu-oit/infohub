#!/bin/sh

if [ -z "${HANDEL_PARAMETER_STORE_PATH}" ] || [ -z "$HANDEL_PARAMETER_STORE_PREFIX" ]; then
    echo "Not running in AWS"
    exit 0
fi

PARAMETERS=`aws ssm get-parameters-by-path --path ${HANDEL_PARAMETER_STORE_PATH} --with-decryption`

for row in $(echo ${PARAMETERS} | jq -c '.Parameters' | jq -c '.[]'); do
    KEY=$(basename $(echo ${row} | jq -r '.Name'))
    VALUE=$(echo ${row} | jq -r '.Value')

    export ${KEY}=${VALUE}
done

export CAKE_DEFAULT_DB_HOST=${DB_ADDRESS}
export CAKE_DEFAULT_DB_USERNAME=$(aws ssm get-parameter --name ${HANDEL_PARAMETER_STORE_PREFIX}.db.db_username --with-decryption | jq -r '.Parameter.Value')
export CAKE_DEFAULT_DB_PASSWORD=$(aws ssm get-parameter --name ${HANDEL_PARAMETER_STORE_PREFIX}.db.db_password --with-decryption | jq -r '.Parameter.Value')