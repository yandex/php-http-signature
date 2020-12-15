# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0]
### Added
- The library now implemented RFC [draft cavage http signature v10](https://tools.ietf.org/html/draft-cavage-http-signatures-10)

## [1.0.1] - 2019-11-06
### Fixed
- Signing of not unique tuple of headers.
- Verifying of signature with not unique tuple of headers.
- Realizations of `KeyStorageInterface` can throw `KeyStorageException` for any 
encapsulated reason such as gateway errors for database or filesystem errors for file-based storage or something else.
- `Digest::fromHeader` throws `UnknownDigestFormatException` instead `\InvalidArgumentException` for digest strings with invalid format.
- The constructor of `AbstractOpenSslPemKey` throws `KeyCorruptedException` instead `\RuntimeException` when `openssl_pkey_get_details` cannot find type of key by it's resource.
- Actualized exceptions in docblocks of all services and interfaces that can be actually throwed.
- Fixed some inaccuracies in descriptions of methods' parameters in docblocks
