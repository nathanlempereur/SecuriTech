#!/bin/bash

for el in $(lxc-ls);do
lxc-start -n "$el" 
lxc-attach -n "$el" -- /bin/bash -c "ip route add default via 10.0.3.250"
done
