#!/bin/sh

# Pass blockchain env vars from Docker into Apache (mod_php doesn't inherit the shell env)
for var in CIRCUIT_LIFECYCLE_LOGGER BLOCKCHAIN_RPC_URL BLOCKCHAIN_CHAIN_ID BLOCKCHAIN_SIGNER_PRIVATE_KEY BLOCKCHAIN_SIGNER_ADDRESS; do
  val=$(eval echo \$$var)
  if [ -n "$val" ]; then
    echo "export $var=$val" >> /etc/apache2/envvars
  fi
done

# BLOCKCHAIN_CONTRACT_ADDRESS arrives late via the shared volume written by the hardhat container
if [ -f /shared/blockchain.env ]; then
  export $(cat /shared/blockchain.env | xargs)
  sed 's/^/export /' /shared/blockchain.env >> /etc/apache2/envvars
fi

cp $MEICAN_DIR/docker_for_build/db.php $MEICAN_DIR/config/ \
 && sed -i "s/MYSQL_DATABASE/$MYSQL_DATABASE/" $MEICAN_DIR/config/db.php \
 && sed -i "s/MYSQL_USER/$MYSQL_USER/" $MEICAN_DIR/config/db.php \
 && sed -i "s/MYSQL_PASSWORD/$MYSQL_PASSWORD/" $MEICAN_DIR/config/db.php \
 && chown -R meican:meican $MEICAN_DIR/config $MEICAN_DIR/runtime $MEICAN_DIR/web/assets \
 && su meican -c "/usr/local/bin/composer install --working-dir=$MEICAN_DIR" \
 && ln -sf $MEICAN_DIR/vendor/bower-asset $MEICAN_DIR/vendor/bower \
 && ln -sf $MEICAN_DIR/vendor/npm-asset $MEICAN_DIR/vendor/npm \
 && su meican -c "php $MEICAN_DIR/yii migrate --interactive=0" \
 &&  echo "Running seed scripts..." \
 && for f in $MEICAN_DIR/tests/seed/*.sql; do \
    echo "Seeding $f" \
    && mysql -h db -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "$f"; \
    done \
 && echo "Seed complete." \
 && service apache2 start \
 && echo "\033[0;32m MEICAN started successfully - $ENV_TEXT \033[0m " \
 && echo "\033[0;32m Running on http://localhost:$MEICAN_PORT \033[0m " \
 && /bin/bash