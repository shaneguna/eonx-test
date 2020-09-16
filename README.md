> Note: Run setup once
    
# Local Docker Environment

* Navigate into the project directory

    ```bash
        cd ~/project-directory
    ```

* Setup script

    ```bash
        chmod u+x local.sh
        ./local.sh
    ``` 

* Verify installation by visiting [http://localhost](http://localhost/).

# Docker Commands Reference

* Start up docker

    ```bash
        docker-compose up -d
    ```
    
* Terminate running instance

    ```bash
        docker stop [OPTIONS] CONTAINER [CONTAINER...]
    ```
    
    
    
# Dependencies

* PHP >= 7.4
* MySQL >= 5.7
* [Composer](https://getcomposer.org/)
* [docker-compose](https://docs.docker.com/compose/install/#install-compose) >= 1.24
* [Docker](https://docs.docker.com/install/) >= v19.03