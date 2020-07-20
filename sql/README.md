# Database config folder

### init.sql intialises the DB.
#### you can customize the test values for you event. 
### postgres.conf is custom config for better performance of postgresql.
#### you can customize this to you liking. when running the container, you need to pass the CMD `postgres -c config_file=/etc/postgresql.conf` if not passed postgres will run with default config.