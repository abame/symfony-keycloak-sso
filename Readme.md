# Nginx PHP MySQL Keycloak SSO

## Run Application

To have the application running clone the repository and execute the following commands:

```sh
$ docker-compose build
$ docker composer up -d
```

## Create database

First update the database configuration `DATABASE_URL` in `.env` file with the correct values and then run the following commands:

```sh
$ docker-composer exec php bin/console doctrine:database:create
$ docker-composer exec php bin/console doctrine:schema:update --force
```

Update also `IDP_ENTITY_ID` and `IDP_XML_CONFIG` in `.env` file in case you change the configuration path for the xml metadata or entity id URL.

In case entity id URL is changed a new client has to be created in keycloak.

## Test the application

You can access the application in `http://127.0.0.1/` and try to login.
You will be redirected to keycloak UI to either login or register, and you will be redirected back to symfony application with a logged-in user session

## Access Keycloak admin panel

The admin panel is accessible in `http://127.0.0.1:8080/auth/admin/` and you can login with username `root` and password `root`