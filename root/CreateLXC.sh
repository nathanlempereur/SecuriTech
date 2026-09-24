#!/bin/bash

echo "Creation de conteneur LXC :"
echo ""
read -p "Quelle nom de conteneur (Zone) ? : " nom

sudo lxc-create -n "$nom" -t download -- -d debian -r bookworm -a arm64


