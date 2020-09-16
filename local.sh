#!/bin/bash
COMMAND="rm -r eonix-exam"
if [ -d "eonix-exam" ]
then
    echo "Directory exists."
    if [ "$(ls -A eonix-exam)" ]; then
        echo -n "Do you want to run $ $COMMAND? [N/y], selecting (N)o runs project commands."
        read -N 1 REPLY
        echo
        if test "$REPLY" = "y" -o "$REPLY" = "Y"; then
            $COMMAND && echo 'Folder removed, run script again to create project'
        else
            echo 'Switching to project directory' && cd eonix-exam
            composer install
            cp .env.example .env
            php artisan key:generate
        fi
    fi
else
    composer create-project --prefer-dist laravel/lumen eonix-exam
fi
