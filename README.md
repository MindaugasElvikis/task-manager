## Installation steps

- First clone this repository.
- `cd task-manager`
- Copy example environment file `cp .env.example .env`
- Install composer dependencies (https://laravel.com/docs/8.x/sail#installing-composer-dependencies-for-existing-projects )`docker run --rm -u "$(id -u):$(id -g)" -v $(pwd):/var/www/html -w /var/www/html laravelsail/php81-composer:latest composer install --ignore-platform-reqs`
- Run the application`./vendor/bin/sail up -d`
- Generate application key `docker exec -it task-manager_laravel.test_1 php artisan key:generate --ansi`
- Generate JWT secret key `docker exec -it task-manager_laravel.test_1 php artisan jwt:secret`
- To execute migrations run `docker exec -it task-manager_laravel.test_1 php artisan migrate`
- To run tests execute `docker exec -it task-manager_laravel.test_1 php artisan test`
