# Introduction to HTTPS, TLS and X.509 certificates

In this repo you'll find an introduction to the **Hypertext Transfer Protocol Secure** (**HTTPS**). To make it very simple, **HTTPS** is nothing more then **HTTP** send encrypted using a version of the **Transport Layer Security** (**TLS**) protocol. Therefore there is a brief introduction into the latest 2 versions - TLS 1.2 and 1.3. In the process of TLS communication, **X.509** certificates are used to validate the server and optionally the client as well. Therefore there is a section about **X.509** certificates as well.

## Table of Contents

[Main document page](/Learning%20HTTPS.md)

1. [Internet Protocol Recap](/Learning%20HTTPS.md#1-internet-protocol-recap)

2. [Browser to Website connection](/Learning%20HTTPS.md#2-browser-to-website-connection)

3. [Introduction to HTTPS](/Learning%20HTTPS.md#3-introduction-to-https)

4. [Cryptopgraphic Methods](/Learning%20HTTPS.md#4-cryptopgraphic-methods)

    4.1. [Introduction to Cryptography](/Learning%20HTTPS.md#41-introduction-to-cryptography)

    4.2. [Symmetric Cryptography](/Learning%20HTTPS.md#42-symmetric-cryptography)

    4.3 [Asymmetric Cryptography](/Learning%20HTTPS.md#43-asymmetric-cryptography)

    4.4. [Diffie-Hellman Exchange](/Learning%20HTTPS.md#44-diffie-hellman-exchange)

    4.5. [Message Digests (Hashes)](/Learning%20HTTPS.md#45-message-digests-hashes)

    4.6. [Message Authentication Code (MAC)](/Learning%20HTTPS.md#46-message-authentication-code-mac)

    4.7. [Digital Signatures](/Learning%20HTTPS.md#47-digital-signatures)

    4.8. [Forward Secrecy](/Learning%20HTTPS.md#48-forward-secrecy)

5. [X.509 Certificate](/Learning%20HTTPS.md#5-x509-certificate)

    5.1. [Introduction to X.509](/Learning%20HTTPS.md#51-introduction-to-x509)

    5.2. [Certificate Structure](/Learning%20HTTPS.md#52-certificate-structure)

    5.3. [Certificate Signature Structure](/Learning%20HTTPS.md#53-certificate-signature-structure)

    5.4. [Verifying the Chain of Trust](/Learning%20HTTPS.md#55-verifying-the-chain-of-trust)

    5.5. [Certificate File Formats and File Extensions](/Learning%20HTTPS.md#56-certificate-file-formats-and-file-extensions)

    5.7. [Certificate Revocation](/Learning%20HTTPS.md#57-certificate-revocation)

    5.8. [Certificate Handling by the Client](/Learning%20HTTPS.md#58-certificate-handling-by-the-client)

    5.9. [Certificate Validation Failure](/Learning%20HTTPS.md#59-certificate-validation-failure)

6. [TLS 1.2 in Detail](/Learning%20HTTPS.md#6-tls-12-in-detail)

    6.1. [TLS 1.2 Full Handshake](/Learning%20HTTPS.md#61-tls-12-full-handshake)

    6.2. [TLS 1.2 Abbreviated Handshake](/Learning%20HTTPS.md#62-tls-12-abbreviated-handshake)

7. [TLS 1.3 in Detail](/Learning%20HTTPS.md#7-tls-13-in-detail)

    7.1. [TLS 1.3 Full Handshake](/Learning%20HTTPS.md#71-tls-13-full-handshake)

    7.2. [TLS 1.3 Session Resumption and PSK](/Learning%20HTTPS.md#72-tls-13-session-resumption-and-psk)

    7.3. [TLS 1.3 0-RTT Data](/Learning%20HTTPS.md#73-tls-13-0-rtt-data)

8. [Getting a Certificate](/Learning%20HTTPS.md#8-getting-a-certificate)

9. [HTTP Strict Transport Security (HSTS)](/Learning%20HTTPS.md#9-http-strict-transport-security-hsts)

There is also a glossary:

A. [Glossary](etc/Glossary.md)

The sources are specific references (HTML anchors) to the sections of the source material, which are identified by superscript UTF-8 numbers (`⁰¹²³⁴⁵⁶⁷⁸⁹`). If one sentence has multiple sources for the information provided, they are separated by `ᐟ`. So e.g. ¹⁵³ᐟ²⁸ references source number 153 and 28 in the "Sources" markdown file:

B. [Sources](etc/Sources.md)

The "References" contains each used source material once without anchors:

C. [References](etc/References.md)

## Acknowledgement

I have to thank the manufactures of my PC hardware and my PC for not breaking down or crashing while writing this having 40 GB of commmited memory. :fire:

No seriously, most of the [Cryptopgraphic Methods section](/Learning%20HTTPS.md#4-cryptopgraphic-methods) comes from the [Encryption, Authentication Survival Guide from the ZYTRAX, Inc. website](https://www.zytrax.com/tech/survival/encryption.html) and the company gave me permission to use their material, so a big thankyou to [ZYTRAX, Inc.](https://www.zytrax.com/) :pray:

I also want to thank the [security.stackexchange user mti2935](https://security.stackexchange.com/users/69717/mti2935) [for pointing out that the certificates on wikipedia were printed using OpenSSL](https://security.stackexchange.com/questions/233880/where-are-field-names-of-decoded-human-readable-x-509-certificates-specified/233883#233883). :pray:

## Donation

You can leave all your money here:

<a href="https://paypal.me/goulashsoup">
    <img src="img/burns-paypal.jpg">
</a>

## Contribution

As a human being i will have made and will make a lot of mistakes of all kinds. Fortunately for digital stuff, mistakes can be corrected. If you find language errors (i'm not a native english speaker and hate commas), technical inaccuracies, wrong or unfullfilling sources, or just have suggestions to enhance the quality of this writing, you can create issues or better, pull requests. It'll take time, but i probably will come back to those every few month.

## Contact

On my [profile page](github.com/goulashsoup) you'll find a mail address as a logged in github user.
