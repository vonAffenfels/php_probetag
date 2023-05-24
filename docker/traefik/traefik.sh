#!/bin/bash

if docker ps --format '{{ .Names }}' | grep -q traefik; then
    echo "Traefik is already running"
    exit 0
fi

if !(docker network ls | grep -q traefik); then
    docker network create traefik
fi

IMAGE="traefik:latest"
docker run \
    --rm \
     -d \
     --name traefik \
     --privileged \
     -p 80:80 \
     -p 443:443 \
     -p 8080:8080 \
     --network traefik \
     -v /var/run/docker.sock:/var/run/docker.sock \
     -l "traefik.enable=true" \
     -l 'traefik.http.routers.dashboard.rule=Host(`traefik.localhost`)' \
     -l "traefik.http.routers.dashboard.service=api@internal" \
     "${IMAGE}" \
     --providers.docker \
      --api \
      --api.insecure \
      --entrypoints.web.address=:80 \
      --entrypoints.web-secure.address=:443 \
      --providers.docker.exposedbydefault=false \
      --providers.docker.network=traefik \
     || echo traefik already running
