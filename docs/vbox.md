On mac it may be necessary to run:

```shell
VBoxManage dhcpserver remove --netname HostInterfaceNetworking-vboxnet0
VBoxManage modifyvm "VM name" --natdnshostresolver1 on
```

See: 
https://github.com/mitchellh/vagrant/issues/3083
