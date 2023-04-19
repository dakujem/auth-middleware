
# Changelog

> 📖 back to [readme](readme.md)

Auth-middleware follows semantic versioning.\
Any issues should be reported.


## v1.2

Provides means for mitigation of security vulnerability **CVE-2021-46743** by using the new `Secret` configuration object.  
The peer library for handling tokens `firebase/php-jwt` must be upgraded to v5.5 in order to do so.  

1. use a single secret+algorithm combination
    - either using the `Secret` object instead of string constants when using `AuthWizard` or `AuthFactory`
    - or passing an array with a single algorithm to the `$algos` parameter of `FirebaseJwtDecoder` constructor when using the decoder as standalone
2. use multiple `Secret` objects and pass them to the `$secret` parameter AND use "kid" JWT header parameter when encoding the JWT
    - the JWT encoding must also factor-in the `kid` parameter when using multiple possible secret+algorithm combinations 

For more information, see https://github.com/firebase/php-jwt/issues/351.


## v1.1

Improved default injector:
- `TokenManipulators::attributeInjector` now accepts a callable
with signature `fn(Throwable): mixed` for producing a value to be written to the error attribute.
- The default injector will no longer prefix the exception messages with `Token Error: ` prefix.


## v1.0

The initial release.
