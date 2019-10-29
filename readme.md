# Scrubtool
A tool for helping manage a variety of suppression lists using a list file.

## Feature Priority

* Hash: Generate a hashed file to be used for offline scrubbing.
* Scrub: Remove records your file that match one or more suppression list/s.
* List: Manage a suppression list (DNC/DNE) for anyone use for scrubbing leads. 
* Enhance: Normalize/filter/append your file.

## Getting Started

    composer install
    npm install
    npm run development
    php artisan migrate
    php artisan queue:work --queue=1,2,4 &
    php artisan serve
