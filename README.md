# Eden
Eden is the "fork point" for any PHP based project. 

## TODO

If you are reading this

- `sudo php init.php`
- Replace this README.md


## If you are making a EBB ETL project
You need to read the contents of the [EBB](https://github.com/docgraph/EBB/) project to see how to work with the various ETL related files. 


## if you are making a web application

We use google as our domain registrar and DNS hoster. 
* If it is not for public use, then use the set.care domain name, with something.set.care. 
* If clients will use the new website then use something.careset.com instead. 

Either way, get a new domain name, and point the domain at your servers IP address.

Next, get encryption working on your system following the letsencrypt [certbot](https://certbot.eff.org/) method. Usually this is as simple as `sudo certbot --apache` once certbot has been installed. 

After that, you need follow the instructions for getting a new app registered with the auth server, downloading the corresponding credentials, and then protecting parts of your interace with the authentication system. 

You need to read the contents of the [Auth](https://github.com/CareSet/AuthenticationServer) for those instructions. 

## Logout
To logout, redirect the url to `/auth/cs/logout`, this will call the JWTAuthClient to logout of the AuthServer
