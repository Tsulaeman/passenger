## Getting started

Run `docker compose up` in the root directory

Run `docker exec -it passenger-backend-1 php artisan migrate`

App should run on `http:localhost:8000`

### Importing the postcodes

Run `docker exec -it passenger-backend-1 php artisan app:download-postcodes`

### Services

Index `http:localhost:8000/api/postcodes`

Search `http:localhost:8000/api/postcodes/search?postcode={postcode substring}`

Near `http:localhost:8000/api/postcodes/near?latitude={lat}&longitude={long}`


### Considerations

The csv file has around 1.7 million row, chunking and bulk insert was used for performance optimisation.

A production app would definitely have higher memory, but it was increased in the import script (which is not ideal, ii should be set in the ini file).

Async jobs like queues should be used with each chunk handled by a different job.

A task like this should be handled in a way that it is non-blockng to the i/o


