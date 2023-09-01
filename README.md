# nl-www

mynl.pl website

# Run locally

```sh
(cd src && docker-compose -f docker-compose.local.yml up)
```

and open `localhost:8000`

# Deploy and restart server (using nl-cli tool)

```sh
mynl deploy && mynl restart
```