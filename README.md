# 🌐 nl-www

This repository is holding [mynl.pl website](https://mynl.pl/).

ℹ️ Note: this is just the public part of the website, not including the administration panel.

# 🏠 Run locally

You can run the project locally using the below command. It will initialize the server on the `localhost:8000`.

```sh
(cd src && docker-compose -f docker-compose.local.yml up)
```

# 🚀 Deploy and restart server (using nl-cli tool)

Deploying, restarting, and stopping the www server is integrated with [GitHub Actions](https://github.com/nl-squad/nl-www/actions).

To deploy and restart from the local environment you need [nl-cli-tool](https://github.com/nl-squad/nl-cli-tool).

```sh
mynl deploy && mynl restart
```
