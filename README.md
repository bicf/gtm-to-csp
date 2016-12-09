# GTM-to-CSP

This's a tool (PHP written) to suit a Google-Tag-Manager's json for the Content Security Policy.

GTM to CSP acts in two different ways:
- calcs the hash all pieces of _inline_ javascript using **sha256**, **sha384** and **sha512** algorithm
- creates a javascript file for each pieces of _inline_ javascript and replace'em in a new json.

## Usign the the hash inside the CSP header
After upload the Json, foreach snippet of javascript you can see the hash signature already single quote wrapped: 

```
'sha512-7CCgs3FeYW7RI1jQbU/pfkhrTtm1RMv72oKk3lkUnKur2TxgsDwDXxkUeW4jsWKaczFUJMgGPDGfrQ7jhXECqA=='
'sha384-gyM24Qn6dnZGiSr2i9BbqtgPa4R1Nnvr0+X6PaH4NjFFZv2ke5NRZgKcJomvtKrs'
'sha256-8sUR+UIC8tljDOZZN55DSB1n/Ikpx1d5e69iInQ6L6A='
```

the CSP header will change from:

`script-src 'local' www.other-stuff.com;`

to:
``` 
script-src 'local'  www.other-stuff.com 'sha512-7CCgs3FeYW7RI1jQbU/pfkhrTtm1RMv72oKk3lkUnKur2TxgsDwDXxkUeW4jsWKaczFUJMgGPDGfrQ7jhXECqA==' 'sha384-gyM24Qn6dnZGiSr2i9BbqtgPa4R1Nnvr0+X6PaH4NjFFZv2ke5NRZgKcJomvtKrs' 'sha256-8sUR+UIC8tljDOZZN55DSB1n/Ikpx1d5e69iInQ6L6A=';
```

## Using a new JSON

After "upload" the json you can "download" a updated version where the _inline_ pieces of javascript ar replaced with external javascripts.
Other html tags like _noscript_ or other _script_ tags **not** javascript or other _script_ tags **javascript** having the source (*src*) attribute are leaved untouched.

### Example
From:
```javascript
{
    "type": "TEMPLATE",
    "key": "html",
    "value": "<script>\nconsole.log('Hello world!');\n</script><noscript><img src=\"https://www.example.com/HelloWorld.gif\"></noscript><script type=\"application/ld+json\">{hello:\"world\"}</script>"
}
```
to:
```javascript
  

{
    "type": "TEMPLATE",
    "key": "html",
    "value": "<script type=\"text/javascript\" src=\"/js/f2c511f94202f2d9630ce659379e43481d67fc8929c757797baf6222743a2faf.js\"></script><noscript><img src=\"https://www.example.com/HelloWorld.gif\"></noscript><script type=\"application/ld+json\">{hello:\"world\"}</script>"
},
```

## How to start it

### PHP requirements
Need a php version  **>= 5.4.0**

### start the internal server 
```shell
$ php -S 127.0.0.1:8000 index.php
```

### Operate via browser
With a browser go to:
`http://127.0.0.1:8000`

### the starting page


![preview](https://raw.githubusercontent.com/devivan/gtm-to-csp/master/start.png)

#### required fields
- **Save path**, is the path where the new javascript are saved on localhost (your compute).
- **Scrip source prefix** is the prefix of JS sources
- **GTM JSON** the original config file

## Now you can get the HASHes

A single TAG can contain multiple snippets of javascript

![hashes](https://raw.githubusercontent.com/devivan/gtm-to-csp/master/multiple.png)

Foreach javascript snippet of all TAGs will be produced three signatures: sha256, sha384 and sha512

copy and paste these signature into the CSP header



## or get the new JSON
### first step, download the new JSON
![download](https://raw.githubusercontent.com/devivan/gtm-to-csp/master/preview.png)
- **Save path**, is the path where the new javascript are saved on localhost (your compute).
- **Scrip source prefix** is the prefix of JS sources

### second step 
import the JSON into your container

### last step
remember to publish your container! :D



