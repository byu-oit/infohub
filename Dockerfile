FROM alpine:3.8

RUN apk --no-cache add tzdata \
  && cp /usr/share/zoneinfo/America/Denver /etc/localtime \
  && echo "America/Denver" > /etc/timezone \
  && cp /etc/timezone /etc/TZ \
  && apk del tzdata

RUN apk --no-cache add php7 php7-intl php7-pdo_mysql php7-json php7-curl \
  php7-xml php7-dom php7-ctype php7-openssl php7-pdo_sqlite php7-soap \
  php7-iconv php7-session php7-mbstring php7-simplexml php7-fileinfo \
  php7-tokenizer php7-zlib aws-cli jq \
  && rm -rf /var/cache/apk/* /var/tmp/* /tmp/*

COPY . /cake

WORKDIR /cake

RUN apk --no-cache add php7-apache2 \
  && rm -rf /var/cache/apk/* /var/tmp/* /tmp/* \
  && ln -s /usr/lib /var/www/lib \
  && mkdir /run/apache2 \
  && rm -rf /var/www/localhost/htdocs && ln -s /cake/app/webroot /var/www/localhost/htdocs \
  && sed -i 's/^#LoadModule rewrite_module /LoadModule rewrite_module /' /etc/apache2/httpd.conf \
  && sed -i 's/^#EnableMMAP off/EnableMMAP off/' /etc/apache2/httpd.conf \
  && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/httpd.conf \
  && mkdir -p /cake/app/tmp/cache/models \
  && mkdir -p /cake/app/tmp/cache/persistent \
  && mkdir -p /cake/app/tmp/sessions


RUN echo -e "#!/bin/sh\n\nrm -f /run/apache2/httpd.pid\nexec /usr/sbin/httpd -D FOREGROUND \$*" > /run-apache.sh && chmod +x /run-apache.sh

RUN chown -R apache:root /cake/app/tmp

CMD ["/bin/sh", "./get_config.sh", "&&", "/run-apache.sh"]