# CockpitCMS-SocialLogin
Social Authentication plugin for Cockpit CMS build with HybridAuth

### Configuration (YAML)

```
social:
    enabled: true
    session_ttl: 3600
    group: admin
    facebook:
        client_id: null
        client_secret: null
    google:
        client_id: null
        client_secret: null
```

> Attention: this is the first approach on this feature. I am planning to put some work in this plugin later.

Based on the old [agentejo's Auth0 plugin](https://github.com/agentejo/Auth0).