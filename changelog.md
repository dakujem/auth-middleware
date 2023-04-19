
# Changelog

> 📖 back to [readme](readme.md)

Auth-middleware follows semantic versioning.\
Any issues should be reported.


## v2.0

This update reflects security vulnerability patch for **CVE-2021-46743** from `firebase/php-jwt` package in version 5.5 and 6.  
As a result, the interface of `FirebaseJwtDecoder` and certain `AuthWizard` methods that create the decoder have been changed.

- [BC break] dropped PHP 7 support; PHP 8 is now required
- [BC break] removed `AuthFactory::defaultDecoderFactory`, please use `fn() => AuthWizard::defaultDecoder( new Secret($secret,$algo) )` instead
- [BC break] changed the constructor of `FirebaseJwtDecoder` to only accept `SecretContract` implementations
- [BC break] using `AuthWizard::defaultDecoder`, `AuthWizard::decodeTokens()` and `AuthWizard::factory()->decodeTokens()` with `string` `$secret` argument will now only decode tokens using the single default `HS256` algorithm
    - previously the same calls resulted in use of any one of the three `HS256`, `HS512`, `HS384` algorithms (the attack vector)
    - to mitigate the issue, use an array of key-algo pairs (`Secret[]`) along with `kid` header parameter (see [section 4.5 of RFC 7517](https://www.ietf.org/rfc/rfc7517.txt))
- the default `FirebaseJwtDecoder` now only works with `firebase/php-jwt` versions 5.5 and 6+ (`5.5.* - 6.*`)
- added `AuthWizard::defaultDecoder` method that directly returns an instance of the `FirebaseJwtDecoder` decoder

> For more details, see [this issue](https://github.com/firebase/php-jwt/issues/351) or [release notes](https://github.com/firebase/php-jwt/releases/tag/v6.0.0).


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
