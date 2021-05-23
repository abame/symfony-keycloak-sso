FROM alpine:3.4

RUN apk add --update nginx
RUN rm -rf /var/cache/apk/* && rm -rf /tmp/*

ADD nginx.conf /etc/nginx/
ADD symfony.conf /etc/nginx/conf.d/

RUN adduser -D -g '' -G www-data www-data

RUN chown -R www-data:www-data /var/lib/nginx /var/log/nginx \
    && chmod -R 755 /var/lib/nginx /var/log/nginx

CMD ["nginx"]

EXPOSE 80
EXPOSE 443
