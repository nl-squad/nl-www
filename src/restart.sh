#!/bin/bash
cwd="$(dirname "$(realpath "$0")")"

echo -e "\033[0;36mRestarting docker-compose...\033[0m"
detach_arg=$([[ $1 == "detach" ]] && echo "-d")
(cd $cwd && docker-compose up --force-recreate $detach_arg)
