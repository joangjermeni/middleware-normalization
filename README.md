
# Middleware and URL Normalization

### Requirements

- PHP >= 7.2

### Install vendors:
```
composer install
```

### Start PHP's built-in web server

Navigate to your project directory using a command-line tool (like Terminal) and run this command:

```
php -S localhost:8080 -t public/
```

### The url normalization process

Below there are some normalization cases.

1- Change all URI characters into lowercases

 `http://localhost:8080/Change/To/LOWER` -> `http://localhost:8080/change/to/lower`
 
2- Remove multiple slashes from URI

 `http://localhost:8080/remove/////multiple/////slashes` -> `http://localhost:8080/remove/multiple/slashes`
 
3- Remove trailing `/`

 `http://localhost:8080/trailing-slash/` -> `http://localhost:8080/trailing-slash`
 
4- Remove `www` from hostname

 `http://www.localhost:8080/remove-www` -> `http://localhost:8080/remove-www`
 
5- Remove dot-segments

 `http://localhost:8080/remove/test/../dot/./segments` -> `http://localhost:8080/remove/dot/segments`
