#!/usr/bin/env sh

# Open a Bash shell into the workspace Docker container
cd laradock && docker-compose exec --user=laradock workspace bash
