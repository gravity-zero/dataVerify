#!/bin/bash

NAME="*"
FULL=false

# Analyse des options passées en ligne de commande
while [[ "$#" -gt 0 ]]; do
    case $1 in
        -n|--name) NAME="$2"; shift ;;
        -f|--full) FULL=true ;;
        *) echo "Option inconnue: $1"; exit 1 ;;
    esac
    shift
done

CMD="vendor/bin/phpunit --colors --display-warnings"

if [[ "$FULL" == true ]]; then
    CMD="$CMD --testdox"
fi

CMD="$CMD tests/${NAME}.php"

echo "Running command: $CMD"
eval $CMD