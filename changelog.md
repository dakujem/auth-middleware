
# Changelog

> 📖 back to [readme](readme.md)

Auth-middleware follows semantic versioning.\
Any issues should be reported.


## v1.1

Improved default injector:
- `TokenManipulators::attributeInjector` now accepts a callable
with signature `fn(Throwable): mixed` for producing a value to be written to the error attribute.
- The default injector will no longer prefix the exception messages with `Token Error: ` prefix.


## v1.0

The initial release.
