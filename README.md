## authnc Plugin for DokuWiki

This small authentication plugin for the [DokuWiki](http://www.dokuwiki.org)
which uses a Nextcloud instance as authentication backend.

**This is mostly work in progress**

##### Remarks

* This plugin uses the [`OCS-API`](https://docs.nextcloud.com/server/latest/developer_manual/client_apis/OCS/ocs-api-overview.html) from Nextcloud to authorize new users.
* At the moment only `trustExternal`, `logout`, `getGroups` and `getUserCount` are implemented
* `getUsers` is somewhat broken, atm (too many requests)
* The API may behave slowly
* ATM, there are no tests, it was programmed against a live instance with a simple dw instance, see submodules (do not checkout)
* ~The login form throws a `failure 998` invalid syntax, see [this](https://www.freedesktop.org/wiki/Specifications/open-collaboration-services/)~

##### ToDo

* [x] Only allow login for enabled users
* allow only specific groups to login
* allow bidirectional user changes 

##### Usage notes

* To use ACL create the appropriate groups within your NC instance and assign it to your users
* Set the groups within the DokuWiki config to manager or users
* Use the ACL as usually

#### Installation notes

If you install this plugin manually, make sure it is installed in
lib/plugins/authnc/ - if the folder is called different it
will not work!

Please refer to http://www.dokuwiki.org/plugins for additional info
on how to install plugins in DokuWiki.

#### Collaboration

* Pull request are highly welcome
* Issues, feature requests, testing and hints are also welcome

 Please use the bug tracker for any of them.

----
Copyright (C) Henrik Jürges <ratzeputz@rtzptz.xyz>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See the LICENSING file for details
