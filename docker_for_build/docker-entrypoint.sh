#!/bin/sh

cp $MEICAN_DIR/docker_for_build/db.php $MEICAN_DIR/config/ \
 && sed -i "s/MYSQL_DATABASE/$MYSQL_DATABASE/" $MEICAN_DIR/config/db.php \
 && sed -i "s/MYSQL_USER/$MYSQL_USER/" $MEICAN_DIR/config/db.php \
 && sed -i "s/MYSQL_PASSWORD/$MYSQL_PASSWORD/" $MEICAN_DIR/config/db.php \
 && chown -R meican:meican $MEICAN_DIR/config $MEICAN_DIR/runtime $MEICAN_DIR/web/assets \
 && su meican -c "/usr/local/bin/composer install --working-dir=$MEICAN_DIR" \
 && ln -sf $MEICAN_DIR/vendor/bower-asset $MEICAN_DIR/vendor/bower \
 && ln -sf $MEICAN_DIR/vendor/npm-asset $MEICAN_DIR/vendor/npm \
 && service apache2 start \
 && echo "\033[0;32m MEICAN started successfully - $ENV_TEXT \033[0m " \
 && echo "\033[0;32m Running on http://localhost:$MEICAN_PORT \033[0m " \
 && /bin/bash