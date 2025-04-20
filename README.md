# PHP Docsify Server Side Rendering

This is a small PHP project that serves as a [Docsify](https://github.com/docsifyjs/docsify) boilerplate with server-side rendering. It is designed to work with the Docsify static site generator, allowing you to render markdown files into HTML on the server side for SEO and have Docsify handle the client-side rendering.

Uses [parsedown](https://github.com/erusev/parsedown) to render markdown files to HTML on the server.

Try it out: 

```bash
php -S 127.0.0.1:80
```

Then visit any page with a GET client like [xh](https://github.com/ducaale/xh) or [curl](https://curl.se/) to see the server-side rendering in action. For example: `xh "127.0.0.1:80/de/index"` will render the german index page.

## Features

- Server-side rendering of markdown files
- Client-side rendering with Docsify
- Multi-language support
