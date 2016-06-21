Basically there are three commands (`schema:create`, `schema:update` and `schema:drop`) for create, update and drop a database schema and cached entity models. All three commands are described in detail below:

### Command `schema:create`

This command will create the database schema (i. e. all database tables) and all cached entity models. So this is the only command you have to run before you can use the advantages of Laravel Datamapper.

Options:
- `--class={classname}`: Only will create the database schema and model for the given entity class.
- `--dump-sql`: This option outputs the SQL queries.

### Command `schema:update`

This command will update the existing database schema (i. e. all existing database tables) and all cached entity models. Already existing tables that are no longer part of any entity will be deleted (see `--save-mode` option to prevent this).

Options:
- `--class={classname}`: Only will update the database schema and model for the given entity class.
- `--dump-sql`: This option outputs the SQL queries.
- `--save-mode`: When this option is enabled already existing tables and models will not deleted.

### Command `schema:drop`

This command will delete the existing database schema (i. e. all existing database tables that belong to an entity) and all cached entity models.

Options:
- `--dump-sql`: This option outputs the SQL queries.