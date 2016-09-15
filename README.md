markri/wp-sec
=============


[![Build Status](https://travis-ci.org/markri/wp-sec.svg?branch=master)](https://travis-ci.org/markri/wp-sec)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing)

## What is wp-sec?

Wp-sec is an exentsion for wp-cli which checks for Wordpress CVE's at wpvulndb.com. Installed versions of wp core, plugins and themes
can be checked or monitored, so you know when to update your Wordpress installation.

## Using

After installation run:

    wp wp-sec check

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install markri/wp-sec`.

@todo as plugin?

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

## Development

To setup a development environment for code contributions, follow instructions below:
Place wp-cli binary in the bin folder. According to current documentation from WP-CLI you would want to do something like: 
       
       cd bin 
       curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
       chmod +x wp-cli.phar
       mv wp-cli.phar wp
       
Create a docker environment and bring it up like this:
   
       docker-compose up -d
       
Enter your dev environment and create a fresh wordpress installation to test against

       docker exec -ti wpsec-phpcli /bin/bash
       mkdir testsite
       wp core download
       wp core config --dbname=database --dbuser=user --dbpass=password --dbhost=wpsec-mysql
       wp core install --url=http://localhost --title=testsite --admin_user=admin --admin_password=admin --admin_email=mail@mail.com --skip-email
       
Execute

       wp wp-sec version
   
Because the current folder is mounted into the docker container you can open up your favourite IDE to make your changes


### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/markri/wp-sec/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/markri/wp-sec/issues/new) with the following:

1. What you were doing (e.g. "When I run `wp post list`").
2. What you saw (e.g. "I see a fatal about a class being undefined.").
3. What you expected to see (e.g. "I expected to see the list of posts.")

Include as much detail as you can, and clear steps to reproduce if possible.

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/markri/wp-sec/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, please follow our guidelines for creating a pull request to make sure it's a pleasant experience:

1. Create a feature branch for each contribution.
2. Submit your pull request early for feedback.
3. Include functional tests with your changes. [Read the WP-CLI documentation](https://wp-cli.org/docs/pull-requests/#functional-tests) for an introduction.
4. Follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/).


