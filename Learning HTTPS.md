# *Short* Introduction to HTTPS, TLS and TLS certificates

<!-- TODOY: Donatation -->
<!-- TODOY: Contributions -->

How HTTP works, is well, but confusingly spreaded documented in the [MDN Web Docs](https://developer.mozilla.org/en-US/docs/Web/HTTP). You could also download [this text file](etc/Learning HTTP.txt) that includes all Mozilla HTTP pages (from 4th quarter of 2019) and related ones in an indentation based manner.

<!--- (TODOY: Relativen Link überprüfen) -->

HTTPS however has just a [brief one paragraph page](https://developer.mozilla.org/en-US/docs/Glossary/https) simply because it dives into a completely different sphere of technologies very unrelated to the well known web technologies like HTML, CSS, JS etc.

We will explore the depths of hell in 9 sections:

<style>
    ul.toc { list-style: none; }

    ul.toc a { text-decoration: none; }

</style>

<div>
    <ul class="toc">
        <li><a href="#1.-internet-protocol-recap">1. Internet Protocol Recap</a></li>
        <li><a href="#2.-browser-to-website-connection">2. Browser to Website connection</a></li>
        <li><a href="#3.-introduction-to-https">3. Introduction to HTTPS</a></li>
        <li><a href="#4.-cryptopgraphic-methods">4. Cryptopgraphic Methods</a></li>
        <li><a href="#5.-x.509-certificate">5. X.509 Certificate</a></li>
        <li><a href="#6.-tls-1.2-in-detail">6. TLS 1.2 in Detail</a></li>
        <li><a href="#7.-tls-1.3-in-detail">7. TLS 1.3 in Detail</a></li>
        <li><a href="#8.-getting-a-certificate">8. Getting a Certificate</a></li>
        <li><a href="#9.-http-strict-transport-security-(hsts)">9. HTTP Strict Transport Security (HSTS)</a></li>
        <li><a href="#a.-glossary">A. Glossary</a></li>
        <li><a href="#b.-sources">B. Sources</a></li>
        <li><a href="#c.-references">C.
                References</a></li>
    </ul>
</div>


##1. Internet Protocol Recap

Like you probably already know, data sent over the Internet means that the data is encapsulated in a packet of some protocol which is again encapsulated in a packet of some protocol until we reach the topmost layer. The Internet Protocol Suite has 4 layers of protocols.² Here is a list of the layers (from topmost to lowest) together with some protocols of interest for us:

1. Link layer - Ethernet

2. Internet Layer - IP, ICMP

3. Transport Layer - TCP, UDP

4. Application Layer - DNS, HTTP, HTTPS (TLS)

In most cases each protocol packet as a header with information necessary for the protocol followed by the data which again is a packet of some protocol:

    Protocol-Header [data]

    [Ethernet [IP [TCP [HTTP [HTML]]]]]

In the above Example an...

    Ethernet contains an
        IP packet which contains a
            TCP packet which contains an
                HTTP packet which contains
                    HTML.

For the Internet to work, the IP layer is important. Devices are identified by IP addresses that are set as the IP packet destination in the IP header. The packet is send from your device to a router and from their on from router to router until it reaches the requested destination (if it exists).³ The only interesting thing to note is, that there are two IP protocols in use, IPv4 and IPv6, which is referred to as dual-stack IP implementation.⁴ IPv6 was mainly developed because IPv4 addresses are only allowed to be 32 bit long which results in 4,294,967,296 unique addresses and this is by far not enough for more then nearly 8 billion people on earth.⁵ᐟ⁶

Now we take a look at what happens when a website is called in a browser...

##2. Browser to Website connection

When a web address, which is a Uniform Resource Locator (URL) (e.g. `https://en.wikipedia.org/wiki/URL`), is entered into the address bar, the browser...

1. ...will parse the URL:

    - Here we assume that either `http` is explicitly specified as the URL scheme or that the scheme is not explicitly provided and assumed by the browser when calling the website. By default, browsers will then use HTTP.²⁰

    - We also assume that a valid hostname is provided, which mostly will be the domain name of the website we want to open.²¹

2. ...will resolve the servers IP address:⁷

    - The browser will send a Domain Name System (DNS) query to the DNS server currently configured on the operating system (OS) to get the IP address of the web server hosting the website.⁷

    - Because of dual-stack implementation, servers, including DNS servers, can have an IPv4 or IPv6 address or both. OSs mostly have both IPv4 and IPv6 addresses for a DNS server.⁸ The DNS server on the OS was either configured manually or came from messages of other protocols used during IPv4 and IPv6 address configuration.

    - The DNS IPv4 address can be send by a router in a Dynamic Host Configuration Protocol v4 (DHCPv4) Offer message which is send in the process of IPv4 address allocation.⁹ For IPv6, the DNS IPv6 address can be send by a router in a Neighbor Discovery Protocol (NDP) Router Advertisment message.¹⁰

    - Because of the dual-stack implementation, just like the DNS server, the web server can also have an IPv4 or IPv6 address or both. Browsers will use the "Happy Eyeballs" algorithm which specifies that seperate DNS queries (Type `A` and `AAAA`) should be send to request an IPv4 and IPv6 address of the web server.¹¹ᐟ¹² The first address that is returned should be used.¹¹

3. ...will send an HTTP GET request to the server:⁷

    - The IP destination address will be the servers address returned by a DNS query response.

    - Because HTTP is sent over TCP, a TCP connection must be established, which includes a 3 message exchange: `SYN (sent by client) => SYN-ACK (sent by server) => ACK (sent by client)`.¹³ The standard port used for HTTP requests is port 80.¹⁴

    - The server will (hopefully) receive the request and generate a response.¹⁵ Here is were web development does its magic by processing the request in many different mostly complicated ways...

4. ...will receive an HTTP response message sent back by the server:¹²

    - HTTP responses have different status codes.¹⁶ The outcome of what happens after the browser receives an HTTP response depends on the HTTP status code, HTTP headers and the content of the HTTP message.¹⁶

    - Assuming the server is configured for HTTP and not HTTPS, the response to the HTTP GET request will have a `2xx successful` status code which is most likely `200 OK`.¹⁶

    - If the HTTP response contains HTML (with JavaScript and CSS), the browser will parse and render the content which results in a beautiful or ugly website you see on screen.¹⁷

Now we have a brief understanding of what happens with HTTP. Of course the process can differ in many ways e.g. when the website got called before, the DNS query is not necessary because the browser cached the IP address.¹⁸ For HTTPS things will get terrifyingly complicated.

##3. Introduction to HTTPS

> Hypertext Transfer Protocol Secure (HTTPS) is an extension of the Hypertext Transfer Protocol (HTTP).¹
>
> In HTTPS, the communication protocol is encrypted using Transport Layer Security (TLS) or, formerly, Secure Sockets Layer (SSL). The protocol is therefore also referred to as HTTP over TLS, or HTTP over SSL.¹
>
> Transport Layer Security (TLS), and its now-deprecated predecessor, Secure Sockets Layer (SSL), are cryptographic protocols designed to provide communications security over a computer network.¹

So, HTTPS is nothing more then encrypted HTTP contained in a TLS data packet which means the HTTP of the server does not change a bit (except for one tiny extension explained later):

    [Ethernet [IP [TCP [TLS [Encrypted HTTP]]]]]

Assuming the website was not accessed before, the browser does not know to use HTTPS when it is not used as the URL scheme and the domain is not in the preload list of the browser (more about that later). Just like before, the browser will then send an HTTP request. This also includes building up a new TCP connection with the default TCP port 80 for HTTP.

Assuming the server is user-friendly configured for HTTPS, it will send an HTTP redirect response (an HTTP response with a `3xx redirection` status code) with a new HTTPS `Location` specified, which the browser will follow.²²

Because the connection establishment is generic, from now on we call the browser a "client". As soon as the client is aware to use HTTPS the following process applies to establish an HTTPS connection:

1. First a TCP connection has to be established, just like for HTTP only for a different port. *TLS does not have a well-known TCP port number. Instead when used with a higher layer protocol, such as HTTP, that protocol designates a secure variant, HTTPS in the case of HTTP, which does have the well-known (or default) port number 443*.¹⁹

2. Because TLS was revised several times, multiple versions of that protocol exist. Here we will focus on the two latest ones, TLS 1.2 and TLS 1.3.²³ When a TCP connection is build up, a TLS connection has to be established. This process is called **Full Handshake** and requires multiple messages to be exchanged between the browser and the server.²⁴ᐟ²⁵ After the **Full Handshake** is finished, data can be exchanged encrypted which is the **Record Protocol** phase.²⁶ᐟ²⁷ The period in which data can be exchanged, in this case the **Record Protocol** phase, is called a session.⁴⁵ After a session has been closed (see list item 6.), a further session can get re-established with fewer messages and less data in an **Abbreviated Handshake** in TLS 1.2 and a **Session Resumption** in TLS 1.3.²⁸ᐟ²⁹

3. The TLS **Full Handshake** (in 1.2 and 1.3) starts with the client sending a TLS **ClientHello** message.²⁶ᐟ²⁷ The server (if things go well) will answer with a TLS **ServerHello** message.²⁶ᐟ²⁷ Both messages, **ClientHello** and **ServerHello**, are unencrypted but here is where the magic starts.²⁶ᐟ²⁷ The messages, **ClientHello** and **ServerHello**, have some fixed fields and extensions with values that are evaluated to decide the cryptographic methods and inherent parameter values for these methods used to secure some further messages and the session. ²⁵ᐟ²⁶ᐟ²⁷

4. When an unsecured website, that does not use HTTPS, is called, there is no certainty the site is trustworthy, the content presented is legal or even that the data reaches the right server. TLS addresses these issues by requiring the server to have a digital certificate which is "signed" by a higher authority.¹ᐟ³¹  The certificate is sent by the server in a TLS **Certificate** message following the **ServerHello**. The certificate includes information about the website and its owner.³² "Signed" means, a cryptographic method was applied that produced some bitstream which are appended to the certificate information and can be verified by the client.³³ TLS does allow the client to continue the connection when a certificate verification fails.¹²³ If a certificate is invalid, the browser will issue a warning, requiring the user to verify its intention to access the site.³⁴ Although using certificates to provide security is a nice idea, by June 2019 more then 50% of evil phishing sites used HTTPS with valid certificates.³¹

5. After completing the **Full Handshake**, the **Record Protocol** starts in which data is sent in an encrypted manner. Encryption algorithms used to secure the data have different parameters. If multiple data messages are sent and the parameters to encrypt the data stay the same, it is quite easy to imagine, that, if by some magic or theft of information, one encrypted message could be decrypted, the other messages can be decrypted as well.²⁷ᐟ³⁰ If the encryption parameters change for each session independent of previous sessions the encryption system is **forward secure**.³⁰ Both TLS 1.2 and TLS 1.3 provide the possibility to use encryption algorithms that allow **forward secrecy**, but TLS 1.3 goes event further by providing a way in which each message can be decrypted with different parameters.⁴⁶ᐟ²⁸ᐟ⁴⁷

6. A connection is closed with an `Alert` message of specific type in both TLS 1.2 and 1.3.³⁵ᐟ³⁶ At that time, the client will already have saved some necessary informaton to re-establish the connection with an **Abbreviated Handshake** in TLS 1.2 and a **Session Resumption** in TLS 1.3.²⁸ᐟ²⁹

Before getting into TLS more detailed, first we have to gain a little knowledge about the cryptographic methods used.

##4. Cryptopgraphic Methods

###4.1. Introduction

> In cryptography, **encryption** is the process of **encoding information**. This process converts the original representation of the information, known as **plaintext**, into an alternative form known as **ciphertext**.³⁷
>
> For technical reasons, an encryption scheme usually uses a **pseudo-random encryption key** generated by an algorithm.³⁷
>
> In cryptography, a **key** is a piece of information (a parameter) that determines the functional output of a cryptographic algorithm.³⁸ To prevent a **key** from being guessed, keys need to be generated truly randomly and contain sufficient entropy.⁴⁰ A **key** length of 80 bits is generally considered the minimum for strong security with symmetric encryption algorithms.³⁹

In the simplest form (**symmetric cryptography**), 4 things are necessary to encrypt and decrypt data:

1. **plaintext** => The data to be encrypted.

2. **key** => long random bitstream.

3. **encryption algorithm** => A method that gets the **plaintext** and the **key** as its parameters to produce an alternative unreadable form of the **plaintext**, the **ciphertext**.

4. **decryption algorithm** => A method that gets the **ciphertext** and the **key** as its parameters to reverse the unreadable **ciphertext** into the original **plaintext**.

Encryption algorithms should apply the assumption, that it is technically infeasible to decrypt the ciphertext without the key, which is called the **computational hardness assumption**.⁴¹

###4.2. Symmetric Cryptography

> **Symmetric encryption** algorithms use a **single key** (or set of keys) to encrypt and decrypt the data. This **single key** - the **shared secret** - must be securely exchanged between the parties that will use it prior to the actual secure communication.⁴²

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 1 - Symmetric Cryptography⁴³</p>

Summary:

1. One peer creates and shares a secret key (**shared secret**) over a secure method.

2. Both peers use the **shared secret** to encrypt then send + to receive then decrypt **message**s.

###4.3. Asymmetric Cryptography

> **Asymmetric encryption** algorithms use a **pair of keys** - a **public and a private key** - and are generally referred to as **public-key cryptographic systems**.⁴³
>
> In these systems, data (called plain-text in the jargon) that is encrypted with one key can only be decrypted with the paired key or vice versa. Given one key, it is computationally infeasible to derive the paired key.⁴³
>
> Asymmetric encryption works by making one key, called the **public key**, widely available, while maintaining the other key, surprisingly called the **private key**, a secret.⁴³
>
> Public-key systems have one significant limitation. They rely on knowing, or trusting, that the public key which will be used in communications with a person, organization or entity really is the public key of the person or organization and has not been spoofed by a malicious third party. Often a third party securely manages, and attests to the authenticity of, public keys.⁴³

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 2 - Asymmetric Cryptography⁴³</p>

Summary:

1. One peer creates a pair of **public** and **private** key.

2. The creator peer of the **public**-**private** key pair shares the **public key** over an insecure method.

3. A peer that wants to use the **public key** should verify it with help of some third party.

4. Now the **public key** can be used to decrypt data only to be encrypted with the **private key**. Or the **public key** can be used to encrypt data only to be decrypted with the **private key**.

###4.4. Diffie-Hellman Exchange

> The **Diffie-Hellman exchange (DH)** is a method by which two (or in some case more) peers can independently create the same **shared secret** that may be subsequently used in a symmetric cryptographic algorithm.⁴⁴
>
> The original Diffie-Hellman Exchange (now frequently referred to as **Finite Field Diffie-Hellman**) uses exotic maths:⁴⁴
>
>     Y (shared secret) = g (generator) ^ x (secret key) mod p (prime modulator) ⁴⁴

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 3 - Diffie-Hellman Exchange⁴⁴</p>

1. The two peers share `p` and `g` publicly:⁴⁴

       g = 3, p = 17

2. Peer 2 then provides a partial result. First it creates a **private key** `s₂` and calculates a **pre-master secret** (also called **DH public value**) `x₂`...

       x₂ = g^s₂ mod p

    ...and sends that pre-master secret `x₂` to peer 1:⁴⁴

       s₂ = 15 => x₂ = 3^15 mod 17 = 6

3. Peer 1 also creates a **private key** `s₁`, calculates a **pre-master secret** `x₁` and sends it to peer 2:⁴⁴

       s₁ = 13 => x₁ = 3^13 mod 17 = 12

4. Now peer 1 and peer 2 are holding each others **pre-master secret** and can calculate the **shared secret** `Y`...⁴⁴

       Y = xₓ^sᵥ mod p

    ...without each other:⁴⁴

       Y = x₁^s₂ mod p = 12^15 mod 17 = 10

       Y = x₂^s₁ mod p = 6^13 mod 17 = 10

If the numbers for the calculation are high enough, an eavesdropping third party should not be able to decrypt the data in appropriate time.

> If both peers use the same private key every time, then if either key is discovered the current exchange and all previous exchanges can be decrypted. It is not, in the jargon, forward secure. To avoid this one or both of the peers can generate a new private key in every Diffie-Hellman Exchange. This is known as a **Ephemeral Diffie-Hellman** exchange (typically, and confusingly, abbreviated to **DHE**).⁴⁴

> Increasingly, algorithms other than Finite Field DH are being used, notably **Elliptic Curves** (**EC**). During the initial agreement (1) the curve type will be negotiated and then curve specific parameters publically exchanged. This is typically abbreviated to **(EC)DH** and if the exchange is ephemeral **(EC)DHE**.⁴⁴

Summary:

1. Both peers share **parameters** used for a Diffie-Hellman encryption algorithm.

2. Each peer creates a **private key** for themself and uses it to create a **pre-master secret**, also called **DH public key**, which is shared over an insecure method.

3. Each peer can then calculate the **shared secret** with its **private key** and the received **DH public key** of the other peer.

4. Both peers use the **shared secret** to encrypt then send + to receive then decrypt **message**s.

###4.5. Message Digests (Hashes)

A **hash algorithm** creates a unique and relatively small fixed-size **digest** for a given message and is a one-way function, that is a function which is practically infeasible to invert.⁴⁸ᐟ⁴⁹

The digest is sent together with the message.⁴⁸ The hash algorithm is applied to the received message and, if the result matches the received digest, the receiver can be sure the message is unaltered.⁴⁸

If the message and the digest is sent unencrypted, it is obvious, that an attacker could alter the data and the digest so that the receiver would evaluate the received data as valid.⁴⁸

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 4 - Message Digest or One-Way Hash⁴⁸ ⁽ᵃˡᵗᵉʳᵉᵈ⁾</p>

Summary:

1. Run a **hash** algorithm over a **message**, the result is a "**digest**".

2. Send the **message** and the **digest** to the receiving peer.

3. The receiving peer runs the same **hash** algorithm over the **message**. If the **hashed message** equals the **digest**, the content is assumed unaltered.

###4.6. Message Authentication Code (MAC)

> The process of authentication and data integrity can use what is called a **Message Authentication Code** (**MAC**). The **MAC** combines the message digest with a **shared (secret) key**. The key part authenticates the sender, and the hash (or digest) part ensures data integrity.⁵⁰

When the MAC is based on a hash algorithm, it is also called an **HMAC** (**Hash-based Message Authentication Code**).¹²⁵ The **secret key** is distributed over a secure method.⁵⁰ The message is processed by the hash algorithm to create a **TX Digest** which is then encrypted using the chosen algorithm and key.⁵⁰ The message and the encrypted **TX digest** is then sent to the recipient using an insecure method.⁵⁰ The recipient takes the message runs it through the (same) hash algorithm to create a **RX digest**.⁵⁰ The received **TX digest** is decrypted using the chosen algorithm and key.⁵⁰ The resulting **TX digest** compared with the **RX digest**.⁵⁰

> Equality = heaven, inequality = h.....orrible.⁵⁰

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 5 - Message Authentication Code (MAC)⁵⁰ ⁽ᵃˡᵗᵉʳᵉᵈ⁾</p>

Summary:

1. Share a **shared secret** over a secure method.

2. Run a **hash** algorithm over a **message**, the result is a **digest**. Encrypt the digest using the **shared secret**:

       digest = hash(message);
       encrypted_digest = encrypt(digest, shared_secret);

3. Send the **message** and the **encrypted digest** over an insecure method to the receiving peer.

4. The receiving peer runs the same **hash** algorithm over the **message**, which again is the **digest**. The recipient decrypts the received **encrypted digest** with the **shared secret** and checks if it equals the self-calculated **digest**:

       digest = hash(message);
       if(digest == decrypt(encrypted_digest, shared_secret)) {
           Message is unaltered...
       }

###4.7. Digital Signatures

> In the asymmetric or public-key world, the process of authentication and data integrity uses what is called a **digital signature**. The message being sent is again hashed to create a message **digest** to ensure data integrity.⁵¹

The resulting message **digest** is then encrypted using the **private key** of the sender which is the **digital signature**. Both the message and the encrypted digest, as the signature, are sent to the receiving party. The receiver decrypts the signature using the **public key** of the sender, applies the hash algorithm to the message, and, if the results match, both the authenticity of the sender and the integrity of the data are assured.⁵¹

So while anyone can decrypt the message and recover the digest using the universally available public key, only the possessor of the private key can encrypt it - thus proving the authenticity of the source.⁵¹

> The underlying digest provides the message integrity and, since it is encrypted by the senders private key, cannot be modified in transit.⁵¹

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 6 - Digital Signature⁵¹ ⁽ᵃˡᵗᵉʳᵉᵈ⁾</p>

Summary:

1. One peer creates a **public**-**private** key pair and shares the **public key**.

2. The creating peer of the **public**-**private** key pair runs a hash algorithm over the **message**, the result is a **digest**, then encrypts the **digest** with the **private key** which results in the **signature**:

       digest = hash(data);
       signature = encrypt(digest, private_key);

3. The creating peer of the **public**-**private** key pair sends the **message** and the **signature** to the receiving peer.

4. The receiving peer runs the same hash algorithm over the **message**, again the result is the **digest**, then decrypts the **signature** with the **public key** and checks if it equals the calculated **digest**:

       digest = hash(data);
       if(digest == decrypt(signature, public_key)) {
           Data is unaltered...
       }

###4.8. Forward Secrecy

Although the term **forward secrecy** is used in a bunch of RFCs including the ones for TLS 1.2 and 1.3, there is no official definition and the one in wikipedia is borked.¹⁵³ᐟ²⁸ᐟ¹⁹⁹ᐟ²²³ Therefore i've decided to do a definition myself:

> An encrpytion system has the property of **forward secrecy** (also known as **perfect forward secrecy**) if an exposure of session data and encryption attributes, including encryption keys, does not give away encryption attributes which help to reveal other sessions.

Let's take **asymmetric encryption** as an example: The server has its public-private key pair and sends its public key to the client. The client then creates a symmetric key, encrypts it with the public key of the server and sends the encrypted symmetric key to the server. With the assumption, that it is technically infeasible to decrypt the encrypted message, which includes the symmetric key from the client, this is a safe method to share encryption attributes for a session.

With the assumption that the server will not change the public-private key pair, an attacker who stored the data exchange of past sessions is able to encrypt these, once the private key gets revealed. The attacker can use the private key to encrypt the symmetric key for each session and then encrypt the sessions with the symmetric keys. Therefore this encryption system would not be **forward secure**.

Now let's assume the server would create a new public-private key pair for each client/session and makes sure that past key pairs are completely removed from any memory. Now, if one private key gets revealed by an attacker, past sessions can not get encrypted because the corresponding private key is long gone, the attacker would of course be able to save the public key send to the client. This encryption system would be **forward secure** because the necessary encryption attributes of past sessions do not exist anymore.

The encryption system provided in the example is not used in TLS 1.2 and TLS 1.3. The only **forward secure** encryption systems that are used in TLS 1.2 and TLS 1.3 are **DHE** and **ECDHE**: As shown in [section 4.4.](#4.4.-diffie-hellman-exchange), for **DHE**, both peers share a new **DH public key** for each session, but because each of the **DH public key**s has a corresponding **private key**, an attacker is not able to resolve the **shared secret** which is used to encrypt the session. Even if an attacker gets hold of one **shared secret**, past sessions are not compromised because they used a different **shared secret**.

There is the concept of session resumption in both TLS 1.2 and TLS 1.3.¹²⁶ᐟ²⁸ In TLS 1.2, the encryption attributes do not change when a session is resumed.¹²⁶ In TLS 1.3, session resumption may include a so called **pre shared key** (**PSK**) that is send in the initial handshake, but because the message that includes the **PSK** is encrypted with the same encryption attributes for the application data of the initial session, it really doesn't enforce any more security, just an extra step to calculate the session keys.²²⁰ᐟ²⁸ Therefore in both TLS 1.2 and 1.3, resumed sessions really can be seen as belonging to the initial session.

##5. X.509 Certificate

###5.1. Introduction to X.509

**X.509** is the standard defining the format of public key certificates used for TLS.⁵² **X.509** certificates are sent by the server in a TLS **Certificate** message.²⁴ The procedure of handling **X.509** certificates is basically the same in both TLS 1.2 and 1.3, just the usage of keys differs.⁵³ᐟ⁵⁴ Certificates are used to verify the relation between the certificate and its owner and also to encrypt/sign specific TLS messages.⁵²ᐟ⁵⁵ᐟ⁵⁶

> An **X.509** certificate is an **ASN.1** (X.680) structure encoded using the **Distinguished Encoding Rules** (**DER**) of X.690...⁵⁷

**ASN.1** stands for **Abstract Syntax Notation One** and *is a standard interface description language for defining data structures that can be serialized and deserialized in a cross-platform way*.⁵⁸ The data structures are human readable and called **module**s where each **module** has a set of **field**s.⁵⁸ **Field**s can be scalar values or other **module**s.⁵⁹ᐟ⁶⁰

The **DER** are one of three **ASN.1** encoding formats specified in the **X.609** standard.⁶¹ More specifically **DER** is a restricted variant of the **Basic Encoding Rules** (**BER**), a type-length-value encoding.⁶²ᐟ⁶³

> This is an example **ASN.1** module defining the messages (data structures) of a fictitious **Foo Protocol**:
>
>     FooProtocol DEFINITIONS ::= BEGIN
>
>         FooQuestion ::= SEQUENCE {
>             trackingNumber INTEGER,
>             question       IA5String
>         }
>
>         FooAnswer ::= SEQUENCE {
>             questionNumber INTEGER,
>             answer         BOOLEAN
>         }
>
>     END
>
> Assuming a message that complies with the **Foo Protocol** and that will be sent to the receiving party, this particular message (**protocol data unit** (**PDU**)) is:
>
>     myQuestion FooQuestion ::= SEQUENCE {
>         trackingNumber     5,
>         question           "Anybody there?"
>     }
>
> Below is the data structure shown above encoded in **DER** format (all numbers are in hexadecimal):
>
>     30 13 02 01 05 16 0e 41 6e 79 62 6f 64 79 20 74 68 65 72 65 3f
>
> **DER** is a type-length-value encoding, so the sequence above can be interpreted, with reference to the standard `SEQUENCE`, `INTEGER`, and `IA5String` types, as follows:
>
>     30 — type tag indicating SEQUENCE
>     13 — length in octets of value that follows
>       02 — type tag indicating INTEGER
>       01 — length in octets of value that follows
>         05 — value (5)
>       16 — type tag indicating IA5String
>          (IA5 means the full 7-bit ISO 646 set, including variants,
>           but is generally US-ASCII)
>       0e — length in octets of value that follows
>         41 6e 79 62 6f 64 79 20 74 68 65 72 65 3f — value ("Anybody there?")
⁶⁴

###5.2. Certificate Structure

The **X.509** version 3 (`X509v3`) certificate module definition is as follows:⁵⁹

    TBSCertificate  ::=  SEQUENCE  {
          version         [0]  Version DEFAULT v1,
          serialNumber         CertificateSerialNumber,
          signature            AlgorithmIdentifier{SIGNATURE-ALGORITHM,
                                    {SignatureAlgorithms}},
          issuer               Name,
          validity             Validity,
          subject              Name,
          subjectPublicKeyInfo SubjectPublicKeyInfo,
          ... ,
          extensions      [3]  Extensions{{CertExtensions}} OPTIONAL,
          ... }

Some of the **ASN.1** modules used in the `TBSCertificate` module include the usage of globally unique Object Identifiers (**OID**s) which are standardized by the *International Telecommunications Union* (ITU) e.g. the key usage extension:⁵⁷ᐟ⁶⁵ᐟ⁵⁹ᐟ⁶⁶

    ext-KeyUsage EXTENSION ::= { SYNTAX
        KeyUsage IDENTIFIED BY id-ce-keyUsage }
    id-ce-keyUsage OBJECT IDENTIFIER ::=  { id-ce 15 }

Here `SYNTAX KeyUsage IDENTIFIED BY id-ce-keyUsage` and `id-ce-keyUsage ...` specifies that the syntax and the values are standardized with the **OID** description `id-ce-keyUsage`.⁵⁹ᐟ⁶⁶ᐟ⁶⁷ The **OID** description `id-ce-keyUsage` belongs to the **OID** `2.5.29.15`.⁶⁷ᐟ⁶⁸ For the **OID** `2.5.29.15`, syntax and valid values are specified as follows:⁵⁹

    keyUsage EXTENSION ::= {
    	SYNTAX KeyUsage
    	IDENTIFIED BY id-ce-keyUsage
    }

    KeyUsage ::= BIT STRING {
    	digitalSignature(0),
    	nonRepudiation(1),
    	keyEncipherment(2),
    	dataEncipherment(3),
    	keyAgreement(4),
    	keyCertSign(5),
    	cRLSign(6)
    }

As you can see, for the key usage extension, only a number of predefined choices can be used. A certificate that does not apply the syntax and values of the **OID** specification is not valid and will not be signed.⁶⁹

Some of the `TBSCertificate` field value types are **Distinguished Name**s (**DN**s). A **DN** consists of multiple **Relative Distinguished Name**s (**RDNs**), each of which is a data-containing element called an attribute with a corresponding value:⁵⁵ᐟ⁵²

    Subject: C=US, ST=California, L=San Francisco, O=Wikimedia Foundation, Inc., CN=*.wikipedia.org

A **DN** can have the following **RDN**s:⁵⁵

- `CN=` commonName e.g. www.example.com

- `C=` country

- `ST=` state or province within country

- `L=` location, nominally an address but ambiguously used

- `OU=` organizationalUnitName, a company division name or similar sub-structure

- `O=` organizationName, typically a company name

The comma seperated list of **RDN**s makes the **DN** (e.g. `C=US, ST=Calif...`).⁷¹

The `TBSCertificate`'s most import fields are the following:⁵⁹

- `signature`:

    An **X.509** certificate is digitally signed by the `issuer` with its private key (see below).⁷² The value of this field identifies the signature algorithm used for the digital signature.⁷³

- `issuer`:

    A **DN** identifying the **trusted authority** that digitally signed the certificate.⁷² **Trusted authorities** are explained in [section 5.4.](#5.4.-certificate-chain).

- `validity`:

    Specifies a time period in which the certificate is valid with the sub-fields `notBefore` and `notAfter`.⁷⁴

- `subject`:

    A **DN** defining the entity (holder) of the certificate. The `CN=` **RDN** includes the domain name e.g. `www.example.com`.⁷⁵ The `CN` value can also make use of wildcards e.g. `*.wikipedia.org`, so that the certificate also verifies all subdomains making it a *wildcard certificate*.⁷⁶

- `subjectPublicKeyInfo`:

    Contains the public key of a specific public-private key pair where the private key is hold by the `subject` entity.⁷⁷ᐟ⁵⁵ᐟ⁶⁹ The public key must be compatible with the key exchange algorithm used by TLS.⁵³ In TLS 1.2 the server might use the private key belonging to this public key to sign message contents.²⁹ᐟ⁷⁸ᐟ⁷⁹

    This field is build of two sub-fields:⁵⁹

    - `algorithm`:

        Identification of the public key algorithm via **OID**.⁵⁷

    - `subjectPublicKey`:

        The public key as a bit string.⁵⁷

 - `extensions`:

    Notable extension fields for **X.509** version 3:⁵⁹

    - `SubjectAltName`:

        `SubjectAltName` stands for **Subject Alternative Name** (**SAN**).⁸⁰

        May include alternative names for the subject, therefore making it a *multi-domain certificate*.⁸⁰ᐟ⁷⁶ More specifically the `SubjectAltName` value is a list with multiple attribute value pairs.⁸⁰ᐟ⁵⁷ For alternative domain names, the `dNSName` attribute is used and values are not limited to subdomains.⁸⁰ᐟ⁵⁷ᐟ⁷⁶

        For example `subject` `CN` could be `www.example.com` and `SubjectAltName` could have multiple `dNSName` value pairs:

          dNSName=example.net,
          dNSName=example.com

    - `KeyUsage`:

        The `KeyUsage` extension defines the purpose of the key contained in the certificate.⁶⁶ᐟ⁵⁷ As already shown above, the values for `KeyUsage` are standardized by an **OID** with the description `id-ce-keyUsage`.⁶⁷ᐟ⁶⁸

    - `BasicConstraints`:

        The `BasicConstraints` extension identifies whether the subject of the certificate is a so called **certificate authority** (**CA**), an organisation that is allowed to sign certificates to users and other organisations.⁸¹ᐟ⁸²ᐟ⁷⁶ It also specifies the maximum number of non-self-issued **intermediate certificate**s that may follow this certificate in a valid certification path.⁸¹ You will learn about **CA**s and **intermediate certificate**s in [section 5.4](#5.4-certificate-chain).

          BasicConstraints ::= SEQUENCE {
               cA                      BOOLEAN DEFAULT FALSE,
               pathLenConstraint       INTEGER (0..MAX) OPTIONAL
          }   

###5.3 Certificate Signature Structure

The whole **DER** encoded `TBSCertificate` value is used for the digital signature created with the private key of the certificate's `issuer`:⁸³ᐟ⁵⁹ᐟ⁸⁴ᐟ⁸⁵

    Certificate  ::=  SIGNED{TBSCertificate}

    SIGNED{ToBeSigned} ::= SEQUENCE {
      toBeSigned  ToBeSigned,
      algorithm   AlgorithmIdentifier{SIGNATURE-ALGORITHM,
                      {SignatureAlgorithms}},
      signature   BIT STRING
    }

`SIGNED` is just one of three different signature versions.⁵⁹ The main point is, that the `TBSCertificate` module is embedded in a `SIGNED` module where the `signature` lies, which builds the **Certificate** module, therefore the whole certificate information is used for the signature.

As you might notice, both `TBSCertificate` and `SIGNED` have a field to specify a signature algorithm (`TBSCertificate::signature` and `SIGNED::algorithm`). The algorithm of the `TBSCertificate` should be the same as the one in the **Certificate** (`SIGNED`) module to protect against *algorithm substitution attacks*.⁸⁶ᐟ⁸⁷

###5.4. Certificate Chain

The `issuer` of a certificate identifies a *trusted authority* that signed the certificate with its private key.⁷² Logically if we do not trust the `subject` of a certificate, we shouldn't trust the `issuer` which means the `issuer` must be verified as well. Just like the server has a certificate identified by the `subject` field, an `issuer` also has a certificate that is signed by a higher authority creating a chain of certificates signed by higher authorities until we reach a self-signed **root certificate**.⁵⁵ᐟ⁸⁸ᐟ⁸⁹ᐟ⁹⁰ This is also called the **chain of trust** in which each certificate *certifies* the next.⁵⁵ᐟ⁸⁸ᐟ⁸⁹ᐟ⁹⁰ The **chain of trust** has the following kinds of certificates:

1. **Root Certificate**:⁸⁸ᐟ⁹¹

    The *highest* certificate in the chain of trust:⁸⁸ A **root certificate** is a self-signed certificate issued by a so called **Certificate Authority** (**CA**) and is distributed by a trusted out-of-band process, mostly **root certificate**s are included in browsers and therefore distributed when a browser gets installed or updated.⁷⁶ᐟ⁸⁸ᐟ³² **Root certificate**s distributed in this way are generically called *trust anchors*.⁸⁴ᐟ⁸⁵

    Self-signed means, that the certificate is created and signed by the **CA** alone.⁸⁵ᐟ⁹¹ᐟ⁷⁶ The signature is created with the private key belonging to the public key that is part of the certificate.⁸⁵ᐟ⁹¹ᐟ⁷⁶ A **root certificate** has the following notable characteristics:

    - The `subject` equals the `issuer` and identifies the **CA**.⁸⁵ᐟ⁹¹

    - The public key of the certificate belongs to the private key that was used to create the certificate's signature.⁸⁵ᐟ⁹¹ Therefore the signature can be verified with the certificate's public key.⁸⁵ᐟ⁹¹

    - The `BasicConstraints` extension has the `cA` field value set `TRUE`.⁸¹

    - Optionally, the `KeyUsage` extension has the `keyCertSign` value set.⁶⁶ᐟ⁵⁷

    **CA**s are deemed to be trusted organisations that are allowed to issue certificates of lower level to other organisations.⁸²ᐟ⁸⁵ As already noted, **root certificate**s are distributed by a trusted out-of-band process.⁷⁶ᐟ⁸⁸ᐟ³² More specifically, **root certificate**s are distributed as part of operating systems and browsers.⁸⁸ᐟ³² An organisation that wants to become a **CA** must therefore apply to **Root Certificate Program**s of big organisations, most notably:⁹²

    - [Microsoft Root Certificate Program](https://docs.microsoft.com/en-us/security/trusted-root/program-requirements) to get a **root certificate** in Windows software.

    - [Apple Root Certificate Program](https://www.apple.com/certificateauthority/ca_program.html) to get a **root certificate** in Apple software.

    - [Mozilla Root Certificate Program](https://wiki.mozilla.org/CA) to get a **root certificate** in Mozilla software but is also used by Chrome on Linux.

    **CA**s come in the form of commercial and non-profit organisations.⁹³ To become a **CA**, an organisation has to conform to an extensive set of security related criteria and undergo an annual audit process, therefore they are deemed to be "trustworthy".⁹⁴ᐟ⁹⁵ᐟ⁹⁶ᐟ⁹⁷ However there have been cases in which **CA**s have been hacked which resulted in wrongfully issued certificates.⁹⁸ᐟ⁹⁹

    Example of a **root certificate** printed with OpenSSL (field names differ from standard):⁹¹ᐟ¹⁰⁰


    >     Certificate:
    >         Data:
    >             Version: 3 (0x2)
    >             Serial Number:
    >                 04:00:00:00:00:01:15:4b:5a:c3:94
    >             Signature Algorithm: sha1WithRSAEncryption
    >             Issuer: C=BE, O=GlobalSign nv-sa, OU=Root CA, CN=GlobalSign Root CA
    >             Validity
    >                 Not Before: Sep  1 12:00:00 1998 GMT
    >                 Not After : Jan 28 12:00:00 2028 GMT
    >             Subject: C=BE, O=GlobalSign nv-sa, OU=Root CA, CN=GlobalSign Root CA
    >             Subject Public Key Info:
    >                 Public Key Algorithm: rsaEncryption
    >                     Public-Key: (2048 bit)
    >                     Modulus:
    >                         00:da:0e:e6:99:8d:ce:a3:e3:4f:8a:7e:fb:f1:8b:
    >                         ...
    >                     Exponent: 65537 (0x10001)
    >             X509v3 extensions:
    >                 X509v3 Key Usage: critical
    >                     Certificate Sign, CRL Sign
    >                 X509v3 Basic Constraints: critical
    >                     CA:TRUE
    >                 X509v3 Subject Key Identifier:
    >                     60:7B:66:1A:45:0D:97:CA:89:50:2F:7D:04:CD:34:A8:FF:FC:FD:4B
    >         Signature Algorithm: sha1WithRSAEncryption
    >              d6:73:e7:7c:4f:76:d0:8d:bf:ec:ba:a2:be:34:c5:28:32:b5:
    >              ...    

2. **Intermediate Certificate**:⁷⁶ᐟ⁸⁸

    **Intermediate Certificate**s are all certificates in the chain of trust below the **root certificate** that are not issued for a web server directly.⁷⁶ Like every other certificate, **intermediate certificate**s have a public-private key pair.¹⁰¹ The highest **intermediate certificate** is signed with the private key of the **root certificate**, while all **intermediate certificate**s below are signed with the private key of the next higher certificate in the chain.⁸⁸ᐟ⁸⁹

    The idea is, that **CA**s give up the authority to assure valid and correct certificate requests to so called **Registration Authority**s (**RA**s), also called **Subordinate CA**s.⁵⁵ᐟ⁷⁶ *An RA is responsible for accepting requests for digital certificates and authenticating the entity making the request*.¹⁰²

    An **intermediate certificate** has the following notable characteristics:

    - The `subject` identifies the **RA**.⁷⁵ᐟ⁵⁵

    - The `issuer` identifies the autority of the next higher certificate, either a **CA** or another **RA**.⁷²ᐟ⁸⁸ᐟ⁵⁷

    - The signature is created with a private key belonging to the `issuer`, the owner of the next higher certificate, whose public key can be used to verify the **intermediate certificate**.⁷²

    - The `cA` field of the `BasicConstraints` extension can be set `TRUE` or `FALSE`.

    There is also the concept of **cross certificate**s:⁸⁸ᐟ⁸⁹

    <div style="padding-left: 30px;">

    **Cross certificate**s are **intermediate certificate**s of a **CA** where the `issuer` is another **CA** and the `cA` field of the `BasicConstraints` extension is set `TRUE`.⁷⁶ᐟ⁸⁹

    They are called **cross certificate**s because each **CA** has its own system for the creation, storage, and distribution of certificates, called a **public key infrastructure** (**PKI**) which is *crossed* when the `issuer` is another **CA**.⁷⁶ᐟ⁸⁹ᐟ⁸⁸

    </div>

    Example of an **intermediate certificate** printed with OpenSSL:¹⁰¹

    >     Certificate:
    >         Data:
    >             Version: 3 (0x2)
    >             Serial Number:
    >                 04:00:00:00:00:01:44:4e:f0:42:47
    >             Signature Algorithm: sha256WithRSAEncryption
    >             Issuer: C=BE, O=GlobalSign nv-sa, OU=Root CA, CN=GlobalSign Root CA
    >             Validity
    >                 Not Before: Feb 20 10:00:00 2014 GMT
    >                 Not After : Feb 20 10:00:00 2024 GMT
    >             Subject: C=BE, O=GlobalSign nv-sa, CN=GlobalSign Organization Validation CA - SHA256 - G2
    >             Subject Public Key Info:
    >                 Public Key Algorithm: rsaEncryption
    >                     Public-Key: (2048 bit)
    >                     Modulus:
    >                         00:c7:0e:6c:3f:23:93:7f:cc:70:a5:9d:20:c3:0e:
    >                         ...
    >                     Exponent: 65537 (0x10001)
    >             X509v3 extensions:
    >                 X509v3 Key Usage: critical
    >                     Certificate Sign, CRL Sign
    >                 X509v3 Basic Constraints: critical
    >                     CA:TRUE, pathlen:0
    >                 X509v3 Subject Key Identifier:
    >                     96:DE:61:F1:BD:1C:16:29:53:1C:C0:CC:7D:3B:83:00:40:E6:1A:7C
    >                 X509v3 Certificate Policies:
    >                     Policy: X509v3 Any Policy
    >                       CPS: https://www.globalsign.com/repository/
    >    
    >                 X509v3 CRL Distribution Points:
    >    
    >                     Full Name:
    >                       URI:http://crl.globalsign.net/root.crl
    >    
    >                 Authority Information Access:
    >                     OCSP - URI:http://ocsp.globalsign.com/rootr1
    >    
    >                 X509v3 Authority Key Identifier:
    >                     keyid:60:7B:66:1A:45:0D:97:CA:89:50:2F:7D:04:CD:34:A8:FF:FC:FD:4B
    >    
    >         Signature Algorithm: sha256WithRSAEncryption
    >              46:2a:ee:5e:bd:ae:01:60:37:31:11:86:71:74:b6:46:49:c8:
    >              ...    

3. **End-Entity Certificate**:⁷⁶ᐟ⁵⁵ᐟ⁸⁸

    An **end-entity certificate** is the certificate of an **end-entity** (**EE**), that is the entity, such as a web server, that is meant to be verified with the chain of trust, merely the purpose of the whole certificate *effort*.⁷⁶ An **end-entity certificate** can not be used to sign other certificates.⁷⁶

    An **end-entity certificate** has the following notable characteristics:

    - The `subject` identifies the **EE**.⁷⁶

    - The `issuer` identifies the autority of the next higher certificate, either a **CA** or an **RA**.⁷²ᐟ⁸⁸ᐟ⁵⁷

    - The signature is created with a private key belonging to the `issuer`, the owner of the next higher certificate, whose public key can be used to verify the **end-entity certificate**.⁷²ᐟ⁸⁸

    - The `cA` field of the `BasicConstraints` extension is set `FALSE`.⁸¹

    Because **end-entity certificate**s are the lowest certificates in the **chain of trust**, they are also called **leaf certificate**s.⁷⁶

    Example of an **end-entity certificate** printed with OpenSSL:⁹

    >     Certificate:
    >         Data:
    >             Version: 3 (0x2)
    >             Serial Number:
    >                 10:e6:fc:62:b7:41:8a:d5:00:5e:45:b6
    >             Signature Algorithm: sha256WithRSAEncryption
    >             Issuer: C=BE, O=GlobalSign nv-sa, CN=GlobalSign Organization Validation CA - SHA256 - G2
    >             Validity
    >                 Not Before: Nov 21 08:00:00 2016 GMT
    >                 Not After : Nov 22 07:59:59 2017 GMT
    >             Subject: C=US, ST=California, L=San Francisco, O=Wikimedia Foundation, Inc., CN=*.wikipedia.org
    >             Subject Public Key Info:
    >                 Public Key Algorithm: id-ecPublicKey
    >                     Public-Key: (256 bit)
    >                 pub:
    >                         00:c9:22:69:31:8a:d6:6c:ea:da:c3:7f:2c:ac:a5:
    >                         af:c0:02:ea:81:cb:65:b9:fd:0c:6d:46:5b:c9:1e:
    >                         9d:3b:ef
    >                     ASN1 OID: prime256v1
    >                     NIST CURVE: P-256
    >             X509v3 extensions:
    >                 X509v3 Key Usage: critical
    >                     Digital Signature, Key Agreement
    >                 Authority Information Access:
    >                     CA Issuers - URI:http://secure.globalsign.com/cacert/gsorganizationvalsha2g2r1.crt
    >                     OCSP - URI:http://ocsp2.globalsign.com/gsorganizationvalsha2g2
    >                 X509v3 Certificate Policies:
    >                     Policy: 1.3.6.1.4.1.4146.1.20
    >                       CPS: https://www.globalsign.com/repository/
    >                     Policy: 2.23.140.1.2.2
    >                 X509v3 Basic Constraints:
    >                     CA:FALSE
    >                 X509v3 CRL Distribution Points:
    >                     Full Name:
    >                       URI:http://crl.globalsign.com/gs/gsorganizationvalsha2g2.crl
    >                 X509v3 Subject Alternative Name:
    >                     DNS:*.wikipedia.org, DNS:*.m.mediawiki.org, DNS:*.m.wikibooks.org, DNS:*.m.wikidata.org, DNS:*.m.wikimedia.org, DNS:*.m.wikimediafoundation.org, DNS:*.m.wikinews.org, DNS:*.m.wikipedia.org, DNS:*.m.wikiquote.org, DNS:*.m.wikisource.org, DNS:*.m.wikiversity.org, DNS:*.m.wikivoyage.org, DNS:*.m.wiktionary.org, DNS:*.mediawiki.org, DNS:*.planet.wikimedia.org, DNS:*.wikibooks.org, DNS:*.wikidata.org, DNS:*.wikimedia.org, DNS:*.wikimediafoundation.org, DNS:*.wikinews.org, DNS:*.wikiquote.org, DNS:*.wikisource.org, DNS:*.wikiversity.org, DNS:*.wikivoyage.org, DNS:*.wiktionary.org, DNS:*.wmfusercontent.org, DNS:*.zero.wikipedia.org, DNS:mediawiki.org, DNS:w.wiki, DNS:wikibooks.org, DNS:wikidata.org, DNS:wikimedia.org, DNS:wikimediafoundation.org, DNS:wikinews.org, DNS:wikiquote.org, DNS:wikisource.org, DNS:wikiversity.org, DNS:wikivoyage.org, DNS:wiktionary.org, DNS:wmfusercontent.org, DNS:wikipedia.org
    >                 X509v3 Extended Key Usage:
    >                     TLS Web Server Authentication, TLS Web Client Authentication
    >                 X509v3 Subject Key Identifier:
    >                     28:2A:26:2A:57:8B:3B:CE:B4:D6:AB:54:EF:D7:38:21:2C:49:5C:36
    >                 X509v3 Authority Key Identifier:
    >                     keyid:96:DE:61:F1:BD:1C:16:29:53:1C:C0:CC:7D:3B:83:00:40:E6:1A:7C
    >    
    >         Signature Algorithm: sha256WithRSAEncryption
    >              8b:c3:ed:d1:9d:39:6f:af:40:72:bd:1e:18:5e:30:54:23:35:
    >              ...    

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 7 - X.509 certificate chain⁸⁸ ⁽ᵃˡᵗᵉʳᵉᵈ⁾</p>

###5.5. Verifying the Chain of Trust

Certificates in the **chain of trust** are signed by a higher authority (except for the **root certificate**) identified by the `issuer`.⁷²ᐟ⁸⁸ The authority will hash the **DER** encoded `TBSCertificate` value and encrypt the hash with the specified encryption algorithm using its own private key, like described in section [**4.7. Digital Signatures**](#4.7.-digital-signatures):⁹⁰ᐟ⁵¹

    digest = hash(encodedTbsCertValue);
    signature = encrypt(digest, issuer_private_key);

The algorithm used to create the public-private key pair is specified in the `subjectPublicKeyInfo` field of the `TBSCertificate` module together with the public key.⁵⁹ᐟ⁵⁷ The signature algorithm which uses the public-private key pair to sign the certificate is specified in the `AlgorithmIdentifier` somewhere in the `SIGNED` module.¹⁰³ Like described earlier, there are 3 versions of `SIGNED` specified.⁵⁹

Both, the public key algorithm and the signature algorithm are identified by a registered **OID**.⁵⁹ᐟ⁵⁷ᐟ¹⁰³ The signature algorithm specifies both, the hash and the encryption/decryption algorithm used.¹⁰⁴ Many but not all algorithms can be found under the **OID** `1.2.840.113549.1.1` e.g. `sha256WithRSAEncryption` (OID: `1.2.840.113549.1.1.11`) specifies that `sha256` is used to create the hash of the certificate and `RSAEncryption` is used to encrypt the created hash with the private key of the `issuer`.¹⁰⁵ **RSA** is a cryptosystem used for both, encryption and decryption.¹⁰⁶

If the **end-entity certificate**'s `issuer` is not a **CA**, therefore the certificate does not *point* to a **root certificate**, all certificates below the **root certificate** are often packaged in a **certificate bundle**.⁷⁶ᐟ¹⁰⁷ A **certificate bundle** is a single file with multiple certificates.⁷⁶ᐟ¹⁰⁷ The **certificate bundle** file will probably contain the certificates in the order in which they need to be verified from the **end-entity certificate** to the last **intermediate certificate**.⁷⁶ᐟ¹⁰⁷ The **certificate bundle** files are used in server software like **Apache** or **nginx** to provide them for the TLS connection.²³²ᐟ²²²ᐟ²³³ Certificates provided via the TLS **Certificate** message must contain the certificates from the **end-entity certificate** to the highest certificate in the chain. The **root certificate** may be omitted because it should already be in possession by the client.¹³⁹ᐟ⁵⁴ Logically, the **end-entity** can always be identified with the `subject` and `subjectAltName` values and the `issuer` is the `subject` of the next higher certificate.

All certificates in the **chain of trust**, from the **end-entity certificate** to the **root certificate**, have to be verified by the browser during the TLS **Handshake** by hashing the **DER** encoded `TBSCertificate` value and decrypting the signature with the specified decryption algorithm, using the public key of the `issuer`'s certificate, which is the next higher certificate in the chain:⁸⁸ᐟ⁵¹

    digest = hash(encodedTbsCertValue);
    if(digest == decrypt(signature, issuer_public_key)) {
        Data is unaltered...
    }

Therefore the public key of the **end-entity certificate** is not directly used in any verification process.⁷⁶

###5.6. Certificate File Formats and File Extensions

Certificates and associated keys come in a bunch of different file formats with two different encodings:¹⁰⁸

1. **DER** encoded as described in the first section.¹⁰⁸ **DER** files can NOT be opened with text editors because they are binary (of course they can be opened with binary text editors).¹⁰⁸ᐟ¹⁰⁹

2. **Privacy Enhanced Mail** (**PEM**) encoded.¹⁰⁸ᐟ¹¹⁰ **PEM** encodes binary **DER** in **base64**, creating a text version (**ASCII**).¹⁰⁸ᐟ¹¹⁰ Objects encoded by PEM include header lines and trailer lines each starting and finishing with precisely 5 dashes to encapsulate the base64 material and provide a human readable indication of its content:¹¹⁰

       -----BEGIN CERTIFICATE-----
       MIIDHDCCAoWgAwIBAgIJALt8VJ...
       ...
       Cfh/ea7F1El1Ym1Zj2v3wLhRl1...
       NH5lEmZybl+m2frlkjUv9KAvxc...
       IFgovdU8YPMDds=
       -----END CERTIFICATE-----        

    Official supported keywords like `CERTIFICATE` and legitimate content are mostly specified in RFC 7468.¹¹¹ Again, the content is **PEM** encoded (**base64** of **DER** encoded content):¹¹¹

    - `CERTIFICATE`:

        Single X.509 certificate.

    - `PKCS7`:

        Contains **Cryptographic Message Syntax** (**CMS**) container which may include multiple certificates. **PKCS** and **CMS** is explained in the next paragraph.

    - `CMS`:

        Again **CMS** container.

    - `PRIVATE KEY`:

        Unencrypted private key in **PKCS#8** container.

    - `ENCRYPTED PRIVATE KEY`:

        Encrypted private key in **PKCS#8** container.

    - `PUBLIC KEY`:

        `SubjectPublicKeyInfo` structure.

> **PKCS** stands for "Public Key Cryptography Standards".¹¹² These are a group of **public-key cryptography standards** devised and published by **RSA Security LLC**.¹¹²

**PKCS #7** defines the **Cryptographic Message Syntax** and is standardized by RFC 2315.¹¹³ **CMS** is a standard for cryptographically protected messages.¹¹³

Possible key and certificate file extensions:¹¹⁴

1. For keys:

    - `.pem`/`.key`/`.p8` => **PEM** encoded **DER** key in PKCS#8 (RFC 5958) container, may be encrypted or unencrypted.¹¹⁴ *PKCS #8 is a standard syntax for storing private key information*.¹¹⁵ But `.pem` can also include a public key as shown above.

    - `.der` => DER encoded raw private key.¹¹⁴

2. For certificates:

    - `.crt`/`.cer` => **PEM** or **DER** encoded, contains an X.509 certificate only, no container.¹¹⁴ᐟ¹⁰⁸

    - `.p12`/`.pfx` => **PKCS #12** is a generic DER encoded container format.¹¹⁴ May contain one or more X.509 certificates and can therefore be used as a **certificate bundle**.¹¹⁶

    - `.p7b` => PKCS#7 (or RFC 5652) CMS DER container encoded as PEM.¹¹⁴ May contain one or more certificates.¹¹⁴

    - `.pem` => **PEM** encoded, but only the keyword gives away the content like `CERTIFICATE`, `PKCS7` and `CMS`.¹¹⁴

    - `.der` => **DER** encoded but again, the file extension does not indicate the content.¹¹⁴

3. For **certificate bundles**:

    Mostly PKCS#7 (`.p7b`) and **PKCS #12** (`.p12`/`.pfx`) are used.¹¹⁴ᐟ¹¹⁶

###5.7. Certificate Revocation

Certificates can be revoked prior to the `notBefore` value of the `validity` field.⁷⁴ᐟ¹¹⁷ᐟ¹¹⁸ Therefore the validity will be checked by clients separately through two different means:

1. **Certificate Revocation List** (**CRL**):¹²¹

    A **CRL** is a list of all certificates that have been revoked.¹²¹ **CRL**s are issued by the **CA** that issued the certificates.¹¹⁷ In practice browsers like Chrome, Firefox and Safari have a **CRL** that is part of the software and updated when the browser software gets updated.¹²²ᐟ⁵⁵

2. **Online Certificate Status Protocol** (**OCSP**):¹¹⁹

    **OCSP** may be used to provide more timely revocation information than possible with **CRL** by providing a service to clients which will be requested to get the certificate status at the time of requesting the web server.²³⁴

    More specifically, when **OCSP** is used, the end-entity certificate of the web server includes an URI in an `AuthorityInfoAccess` extension field, leading to an **OCSP service** provided by the **CA**.¹¹⁸ᐟ²³⁵

    Originally, a client would send an **OCSP request** to the **OCSP service** to request the status information of the end-entity certificate using the certificates serial number. ²³⁴ᐟ²³⁶ᐟ²³⁷ The **OCSP service** should send an **OCSP response** back to the client, which includes a status about the requested certificate.²³⁴ᐟ²³⁸ **OCSP** specifies the ASN.1 modules used for the request and the response messages.²³⁷ᐟ²³⁹ The **OCSP response** has to be valid, meaning that its information must satisfy certain criteria...

    - about the association to the requested certificate (e.g. serial number),

    - about the identities of the parties involved,

    - as well as providing a valid signature as elucidated in the next paragraphs.²⁴⁰

    The status of the certificate can be...²³⁸

    - `good` => Certificate with the requested serial number was not revoked.

    - `revoked` => Indicates that the certificate has been revoked.

    - `unknown` => Responder does not know about the certificate.

    Just because a status is `good` does not mean the certificate is acceptable:²³⁸

    > Response extensions may be used to convey additional information on assertions made by the responder regarding the status of the certificate, such as a positive statement about issuance, validity, etc.

    It is a significant cost for the **CA** to provide responses to every client of a given certificate in real time.²⁴¹ *There are cases where OCSP requests for a single high-traffic site caused significant network problems for the issuing CA.* ²⁴¹

    The TLS **Certificate Status Request** extension addressed this issue partially by providing clients the possibility to send the **OCSP request** via a `status_request` extension in its **ClientHello** message in TLS 1.2 (see [section 6.1](#6.1-tls-1.2-full-handshake)) directly to the server in question.²⁴²ᐟ²⁴³ The server may then provide a copy of a previously acquired **OCSP response** in a TLS **CertificateStatus** message immediately after the **Certificate** message.²⁴³ᐟ²⁴¹ TLS 1.3 also uses the **Certificate Status Request** extension but sends it differently, which is described in one of the paragraphs below.²¹²

    > While it may appear that allowing the site operator to control verification responses would allow a fraudulent site to issue false verification for a revoked certificate, the stapled responses can't be forged...²¹³

    This is because the **OCSP response** is digitally signed, either by the CA that issued the certificate in question or by a delegated authority.²³⁸ᐟ²⁴⁴ The private key used to sign the **OCSP response** must not be the same that was used to sign the certificate in question.²⁴⁴ Instead the CA issues another certificate for the **OCSP response** signer, whose corresponding private key should also be used to sign the **OCSP response**.²⁴⁴ The signer's certificate should be identifiable by a name and a hash of the public key, meaning it is available to the client by other means (e.g. root certificate in the web browser).²³⁹ᐟ²⁴⁵ The signer's certificate can also be provided in the **OCSP response**.²³⁹

    Although one **OCSP request** can include status requests for multiple certificates, an **OCSP request** and **OCSP response** only correspond to one certificate, which may require clients to have multiple **OCSP responses** for other certificates in the certificate chain if they also use **OCSP**.²⁴² Because the **Certificate Status Request** extension (`status_request`) only allows a request for one certificate, intermediate certificates have to be checked through other methods, introducing further delays.²⁴² To mitigate that issue, another TLS **ClientHello** extension, the **Multiple Certificate Status Extension** (`status_request_v2`) allows clients to request multiple **OCSP request**s and servers to include multiple **OCSP response**s in its TLS **CertificateStatus** message in TLS 1.2.²⁴⁶

    As mentioned before, TLS 1.3 does use the original `status_request` extension and deprecates the `status_request_v2`.²¹² In TLS 1.3, the **Certificate** message can include a certificate as well as a set of extensions like the `status_request` **CertificateStatus** message, making it possible to send each certificate together with its **OCSP response**.²¹²

    To clarify, sending a **CertificateStatus** message is optional for all TLS versions.²⁴³ᐟ²⁴⁶

    Even though the first version of the `status_request` extension is already 14 years old (RFC 4366 from April 2006), at the time of this writing a survey from January 2019 by **APNIC** (**Asia Pacific Network Information Centre**, the regional internet address registry for the Asia-Pacific region) suggests that **OCSP** is implemented poorly in browsers as well as in server software:²⁴⁷ᐟ²⁴⁸

    > We observed that:
    >
    > a. 36.8% of OCSP responders experienced at least one outage, which typically lasted a few hours.
    >
    > b. All major browsers other than Firefox do not bother to ensure that stapled OCSP responses are actually included (stapled).
    >
    > c. Neither of the Apache and Nginx web servers prefetch an OCSP response, which introduces unnecessary latency in completing the TLS handshake with clients.

###5.8. Certificate Handling by the Client

To sum up how each X.509 certificate in the chain of trust up to the **root certificate** is handled when received via a TLS **Certificate** message during a TLS handshake, the client (in this case the browser) has to perform the following validation steps:

1. Validate the necessary certificate attributes, such as the `subject` (`subjectAltName`) values and the `issuer`/`subject` for correctness.

2. Check if the **certificate** is revoked by the **CRL** of the browser.

3. Validate requested OCSP response and corresponding certificate status of the **certificate**. Either the OCSP response was received via **CertificateStatus** message or by accessing the responder URI specified in the certificate's `AuthorityInfoAccess` extension field.

4. Validate the certificate's signature as described in  [section 5.5.](#5.5.-verifying-the-chain-of-trust).

If all verification steps succeed, the server is deemed to be trustworthy. The TLS handshake proceeds to establish a secure connection.

###5.9. Certificate Validation Failure

Cases in which the chain of trust is not valid or unfitting for the TLS connection are not specified as *fatal* errors, therefore the client can decide to continue the connection.¹²³

Most browsers will show a warning page encouraging the user to return to *safety* but allowing the user to continue mostly through a hidden butten shown when opening **advanced** information:¹²⁴

<img
    style="margin: 0 auto; display: block;"
    src="data:image/png;base64,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"
/>
<p style="text-align: center;">Fig. 8 - Chrome certificate expired warning¹²⁴</p>

##6. TLS 1.2 in Detail

###6.1. TLS 1.2 Full Handshake

To start a TLS connection a **Full Handshake** is required:¹²⁶

    Client                                               Server

    ClientHello                  -------->

                                                    ServerHello
                                                   Certificate*
                                             ServerKeyExchange*
                                            CertificateRequest*
                                 <--------      ServerHelloDone

    Certificate*
    ClientKeyExchange
    CertificateVerify*+
    (ChangeCipherSpec)
    [Finished]                   -------->
                                              (ChangeCipherSpec)
                                 <--------            [Finished]

    [Application Data]           <------->     [Application Data]

    * Indicates optional or situation-dependent messages that are not always sent.    

    + message is not sent unless client authentication is desired.       

    [] Indicates messages protected using the negotiated bulk-data cipher
       (using the computed shared secret) and are protected by the negotiated MAC.
       The messages may also be compressed.

    () Indicates messages that do not belong to the Full Handshake but are
       necessary nonetheless.

The purpose of the **Full Handshake** is that client and server agree upon security parameters that will be used to secure further **Application Data** messages.¹²⁶ᐟ¹²⁷

Although the messages are shown being separated, they can be sent in coalesced blocks e.g. **ServerHello**, **Certificate** and **ServerKeyExchange** can be sent in one data block.¹²⁸

1. **ClientHello** + **ServerHello**:¹²⁶ᐟ¹²⁹ᐟ¹³⁶

    The **Full Handshake** starts with the Client sending a **ClientHello** message to the server which includes most importantly the supported security attributes the server can choose from to secure data in the **Application Data** message phase:¹²⁶ᐟ¹²⁹

    1. **Cipher Suite**:¹²⁹

        The **ClientHello** includes a list of **cipher suites** in preferred order.¹²⁹ A **cipher suite** is a 2 byte ID value, notated as a pair of hexadecimal numbers, for a set of algorithms used for securing that specific TLS session.¹²⁶ᐟ¹²⁹ᐟ¹³¹ᐟ¹³²ᐟ¹⁷⁸

        For TLS 1.2, the **cipher suite** defines a **key exchange algorithm**, a **bulk cipher algorithm** (including secret key length) used for the messages being sent and a **MAC algorithm**, mostly a HMAC, used to ensure integrity of the message:¹²⁹ᐟ¹³⁰ᐟ¹³²ᐟ¹⁷⁸

           Cipher Suite                            Key          Cipher       Mac  Value
                                                   Exchange

           TLS_RSA_WITH_AES_256_CBC_SHA            RSA          AES_256_CBC  SHA  { 0x00, 0x35 }
           TLS_DH_RSA_WITH_AES_128_CBC_SHA         DH_RSA       AES_128_CBC  SHA  { 0x00, 0x31 }
           TLS_DHE_RSA_WITH_AES_256_CBC_SHA        DHE_RSA      AES_256_CBC  SHA  { 0x00, 0x39 }
           TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA      ECDHE_RSA    AES_256_CBC  SHA  { 0xC0, 0x14 }
           TLS_DHE_RSA_WITH_AES_128_GCM_SHA256     DHE_RSA      AES_128_GCM  SHA  { 0x00, 0x9E }

        The shown **cipher suite**s are not all defined in the TLS 1.2 specification (RFC 5246).¹³¹ In general, new **cipher suite**s can be specified in RFCs and are added to the **TLS Cipher Suite Registry** which is a protocol registry maintained by the **Internet Assigned Numbers Authority** (**IANA**) which includes all **cipher suite**s specified in any RFCs.¹³³ᐟ¹³⁵ᐟ¹³⁴

        > The **Internet Assigned Numbers Authority** (**IANA**) is a standards organization that oversees global IP address allocation, autonomous system number allocation, root zone management in the Domain Name System (DNS), media types, and other Internet Protocol-related symbols and Internet numbers.¹³⁴

        The server will select and set one of the provided **ClientHello** **cipher suite**s in its **ServerHello** message.¹³⁶ If it can not find a match it will respond with a `handshake_failure` **Alert** message, hence the **Full Handshake** can not proceed.¹³⁶ᐟ¹²³

    2. **Compression Method**:¹²⁹

        The **ClientHello** includes a list of supported compression methods.¹²⁹ TLS 1.2 - RFC 5246 does not specify how a server should select a compression method, just that it sets the selected one in the **ServerHello** message.¹³⁶

    3. **Signature Algorithm**:¹³⁶ᐟ¹³⁸

        The **ClientHello** may contain a `signature_algorithms` extension to indicate to the server which signature/hash algorithm pairs must be used in digital signatures.¹³⁶ᐟ¹³⁸ This regards the signatures of all X.509 certificates in the certificate chain sent via the **Certificate** message by the server as well as the signature in the **ServerKeyExchange** message.¹³⁹ᐟ¹⁴⁰

        If the `signature_algorithms` extension is NOT provided, the server will deduce the acceptable signature/hash algorithm from the negotiated **key exchange algorithm** of the **cipher suite**.¹³⁸

    Additionally the **ClientHello** message may include a `cached_info` extension with a `hash_value` fingerprint of the servers **Certificate** message to inform the server that it has its certificates cached.¹⁴¹ᐟ¹⁴²ᐟ¹⁴³

    The client may also request one or more OCSP responses for the certificates of the server using either the `status_request` or the `status_request_v2` extension.²⁴⁶ᐟ²⁴³

    The **ClientHello** as well as the **ServerHello** also include a `session_id` which can be used to resume a session in an **Abbreviated Handshake**, see [section 6.2.](#6.2.-tls-1.2-abbreviated-handshake).¹²⁹ᐟ¹²⁶ᐟ¹³⁶

2. Server **Certificate** + **CertificateStatus**:¹²⁶ᐟ¹³⁹ᐟ²⁴⁶ᐟ²⁴³

    Most **key exchange algorithm**s in the provided **cipher suite**s are two-parted e.g. `DH_RSA` or `DH_DSS`.¹³¹ᐟ¹³⁰ᐟ¹³⁹

    The 1. part really denotes the **key exchange algorithm** while the 2. part just specifies the **public-key cryptosystem** used for the **public key** of the **end-entity certificate** and specifies that the server should be verified.¹³⁹ Therefore `_RSA` requires an **RSA public key** and `_DSS` requires a **DSS public key** in the **end-entity certificate**.¹³⁹

    If the 2. part is `_anon` or `_NULL`, no *public-key cryptosystem* is used and the server doesn't need to send a **Certificate** message.¹³⁹ᐟ¹⁴⁴

    Depending on the **key exchange algorithm** and the `cached_info` extension value of the **ClientHello** message, the server will provide the chain of **X.509 certificate**s, as described in [section 5.4. Certificate Chain](#5.4.-certificate-chain), in a **Certificate** message.¹³⁹ᐟ¹⁴⁵

    The certificates must be in correct order starting with the servers own **end-entity certificate** and ending with the last **intermediate certificate** or optionally the **root certificate**, so that each following certificate certifies the previous one.¹³⁹ The **end-entity certificate** necessarily includes a **public key** of a public-private key pair where the **private key** belongs to the server.¹³⁹

    The certificates are used in the following ways:

    1. To verify the relation between the **end-entity certificate** and its owner.⁵⁵ The client should verify the certificates after receiving the **ServerHelloDone** message.⁷⁰

    2. To encrypt and/or sign TLS messages, specifically dependent on the used **key exchange algorithm**, the **ServerKeyExchange** message might be signed with the private key belonging to the **end-entity certificate**.¹⁴⁶ᐟ¹³⁹ᐟ¹⁴⁰

    > If the client provided a "signature_algorithms" extension, then all certificates provided by the server MUST be signed by a hash/signature algorithm pair that appears in that extension.¹³⁹

    In case the **ClientHello** message includes a `cached_info` and its `hash_value` fingerprint is still valid, which is the case when the certificate chain didn't change, the server also doesn't need to send the certificates.¹⁴⁵ However it needs to send an alternative version of the **Certificate** message including the `hash_value` fingerprint.¹⁴⁵

    If the client has requested one or multiple OSCP responses for the certificates via the `status_request` or the `status_request_v2` extension, the server will send a **CertificateStatus** message immediately after the **Certificate** message which contains either one or multiple OCSP responses, depending on the extension used.²⁴⁶ᐟ²⁴³

3. **ServerKeyExchange** + **ClientKeyExchange**:¹²⁶ᐟ¹⁴⁰ᐟ¹⁴⁷

    These messages are used to create the keys for the **bulk cipher algorithm** and the **MAC algorithm** selected by the server to encrypt the **Finished** messages and ultimately the **Application Data**.¹⁴⁰ᐟ¹⁴⁷ᐟ¹⁴⁸ᐟ¹⁴⁹ The process of creating the keys is consistent for all possible key exchange algorithms:

    1. Derive a **premaster secret** from the key exchange algorithm:¹⁴⁰ᐟ¹⁴⁷ᐟ¹⁵⁰

        There are major differences in how the **premaster secret** gets evaluated for the different key exchange algorithms specified in the **cipher suite**.¹³⁹ᐟ¹⁴⁰ᐟ¹⁴⁷ᐟ¹⁵⁰

        As already mentioned, most **key exchange algorithm**s are two-parted where the 1. part really denotes the **key exchange algorithm** while the 2. part just specifies the **public-key cryptosystem**.¹³⁹

        For **key exchange algorithm** using the `_RSA` or `_DSS` *public-key cryptosystem*, the server will sign the **ServerKeyExchange** using the servers private key belonging to the **end-entity certificate**.¹⁴⁰ᐟ¹⁴⁶ The client should verify the signature using the **end-entity certificate**s public key as described in [section 5.5. Verifying the Chain of Trust](#5.5.-verifying-the-chain-of-trust).⁷⁰ᐟ⁸⁸

        We explore the process for the defined key exchange algorithms:

        - `RSA` - Pure Rivest–Shamir–Adleman:¹³⁹

            0. The **end-entity certificate**s **public key** must be an RSA key.¹³⁹ A **ServerKeyExchange** is NOT send.¹⁴⁰

            1. The client generates a random 48 byte long **pre master secret** and encrypts it using the **public key** of the end-entity certificate.¹⁴⁷ᐟ¹⁵⁰ᐟ¹⁵³

            2. The client sends the encrypted **pre master secret** with a **ClientKeyExchange** to the server.¹⁴⁷ᐟ¹⁵⁰

        - `DH_DSS` + `DH_RSA` - Diffie-Hellman:¹³⁹

            0. The **end-entity certificate** provided by the server must contain **Diffie-Hellman parameters** and the **DH public key** which is contained in the certificate's **public key** part.¹³¹ᐟ¹³⁹ A **ServerKeyExchange** message is NOT sent.¹⁴⁰

            1. The client will use the server's **DH public key** to calculate its own **DH public key**.¹⁴⁷ᐟ¹⁵¹

            2. The client sends its unencrypted **DH public key** with a **ClientKeyExchange** message to the server.¹⁴⁷ᐟ¹⁵¹

            3. The TLS **pre master secret** is the **DH shared secret** which the client calculates with the two **DH public key**s.¹⁵²

        - `DHE_DSS` + `DHE_RSA` + `DH_anon` - Ephemeral Diffie-Hellman:¹³⁹

            0. The Diffie-Hellman parameters and the servers **DH public key** is newly created for each **Full Handshake** thus for every new connection to the server.¹³⁹ᐟ¹⁴⁰ Therefore Ephemeral Diffie-Hellman provides **forward secrecy** because every TLS session has new encryption keys.²⁵⁰ᐟ³⁰

            1. The server sends the new DH parameters and the **DH public key** unencrypted with a **ServerKeyExchange** message to the client.¹⁴⁰ᐟ¹⁵³ For `DHE_DSS` and `DHE_RSA`, the **ServerKeyExchange** message is signed using the server's private key.¹³⁹ᐟ¹⁵³ᐟ¹⁴⁶ᐟ¹⁴⁰ Because `DH_anon` means Diffie-Hellman is used without a server certificate, a **ServerKeyExchange** message must be used to provide the DH parameters and the **DH public key** unsigned to the client.¹⁴⁰

            2. Just like for normal Diffie-Hellman, the client will create and send a newly created **DH public key** with a **ClientKeyExchange** message.¹⁴⁷ᐟ¹⁵¹

            3. The client calculates its **pre master secret** using both **DH public key**s.¹⁵²

        - `ECDHE_RSA` + `ECDHE_ECDSA ` + `ECDH_anon` - Ephemeral Elliptic-Curve Diffie–Hellman:¹⁵⁵

            0. The process is equivalent to *Ephemeral Diffie-Hellman* just that the DH key exchange is based on elliptic curves.¹⁵⁴ Because the parameters and keys are calculated for each session, this key exchange algorithm also provides **forward secrecy**.¹⁵⁵

            1. The server sends the specification of the elliptic curve and corresponding **ECDH public key** unencrypted with a **ServerKeyExchange** message to the client.¹⁵⁶ For `ECDHE_RSA` and `ECDHE_ECDSA`, the **ServerKeyExchange** message is signed using the server's private key.¹⁵⁶

            2. The client will create and send a newly created **ECDH public key** with a **ClientKeyExchange** message.¹⁵⁷

            3. The client calculates its **pre master secret** using both **ECDH public key**s.¹⁵⁸

        The specification for TLS 1.2 - RFC 5246 does not provide any **Ephemeral Elliptic-Curve Diffie–Hellman** key exchange algorithms. These were added in **RFC 8422 - ECC Cipher Suites for TLS**.¹³²

        Like shown above **forward secrecy** is only provided for Diffie–Hellman Ephemeral key exchange methods in which new parameters and a **public key** are provided in a **ServerKeyExchange** message.¹⁵⁹

    2. Calculate a **master secret** using the **pre master secret** as a parameter for the **PseudoRandom Function** (**PRF**):¹⁴⁸ᐟ¹⁶⁰ᐟ¹⁶¹

           session_hash = Hash(handshake_messages);

           master_secret = PRF(
               pre_master_secret,
               "extended master secret",
               session_hash
           )[0..47];

        This is an updated version of the **master secret** calculation defined in **RFC 7627 - TLS Session Hash Extension** to prevent man-in-the-middle attacks.¹⁶⁰ᐟ¹⁶¹ `handshake_messages` is the concatenation of all the exchanged handshake message structures.¹⁶¹ `Hash` is defined as the **data expansion function** function used for the `PRF`, a function using the HMAC specified by the **MAC** of the negotiated **cipher suite**.¹⁶²ᐟ¹⁶³. TLS 1.2 - RFC 5246 specifies that the HMAC is SHA-256 or a stronger standard hash function:¹⁶²

           PRF(secret, label, seed) = P_<hash>(secret, label + seed)

           P_hash(secret, seed) = HMAC_hash(secret, A(1) + seed) +
                                  HMAC_hash(secret, A(2) + seed) +
                                  HMAC_hash(secret, A(3) + seed) + ...

        > `P_hash` can be iterated as many times as necessary to produce the required quantity of data.  For example, if `P_SHA256` is being used to create 80 bytes of data, it will have to be iterated three times (through `A(3)`), creating 96 bytes of output data; the last 16 bytes of the final iteration will then be discarded, leaving 80 bytes of output data.¹⁶²

    3. Use the **master secret** as a parameter of the **PRF** (as defined above) to expand it into a **key block**, a sequence of secure bytes:¹⁴⁹

            key_block = PRF(
                SecurityParameters.master_secret,
                "key expansion",
                SecurityParameters.server_random + SecurityParameters.client_random
            );    


    4. Partition the **key block** to get the necessary keys for the **bulk cipher algorithm** and the **MAC algorithm**:¹⁴⁹

            client_write_MAC_key[SecurityParameters.mac_key_length]
            server_write_MAC_key[SecurityParameters.mac_key_length]
            client_write_key[SecurityParameters.enc_key_length]
            server_write_key[SecurityParameters.enc_key_length]
            client_write_IV[SecurityParameters.fixed_iv_length]
            server_write_IV[SecurityParameters.fixed_iv_length]

4. **ServerHelloDone**:¹²⁶ᐟ⁷⁰

    The server denotes that its finished sending the **ServerHello** and associated messages.¹²⁶ It is not mentioned further when examining the different key exchange algorithms.

    After receiving the **ServerHelloDone** message, the client should verify the servers certificate chain as described in [section 5.8. Certificate Handling by the Client](#5.8.-certificate-handling-by-the-client).⁷⁰

    As already explained in [section 5.9. Certificate Verification Failure](#5.9.-certificate-verification-failure), cases in which the certificate is not valid or unfitting for the TLS connection are not specified as *fatal* errors, therefore the client may decide to continue the connection.¹²³ This includes the following **error alerts**:¹²³

    - `bad_certificate` => A certificate is corrupt, contains signatures that did not verify correctly, etc.¹²³

    - `unsupported_certificate` => A certificate has an unsupported type.¹²³

    - `certificate_revoked` => A certificate was revoked by its signer.¹²³

    - `certificate_expired` => A certificate has expired or is not currently valid.¹²³

    - `certificate_unknown` => Some other (unspecified) issue arose in processing the certificate, rendering it unacceptable.¹²³

5. **CertificateRequest** + Client **Certificate** + **CertificateVerify**:¹²⁶ᐟ¹⁶⁴ᐟ¹⁶⁵ᐟ¹⁶⁶

    Client's **Certificate** and **CertificateVerify** messages are only sent when the server requested a client certificate with a **CertificateRequest** message.¹⁶⁴ᐟ¹⁶⁵ᐟ¹⁶⁶ The **CertificateVerify** is sent after the **Certificate** message and just contains a signature of all previous handshake messages using the **private key** that belongs to the **public key** of the client certificate.¹⁶⁶ᐟ¹²⁶ The server specifies `supported_signature_algorithms` in the **CertificateRequest** message.¹⁶⁴ The **CertificateVerify** signature algorithm must be one of those present in `supported_signature_algorithms` of the **CertificateRequest**.¹⁶⁶

6. **ChangeCipherSpec**:¹²⁶ᐟ¹⁶⁷

    The client and then the server will send a **ChangeCipherSpec** following a **Finished** message.¹⁶⁷ᐟ¹⁶³

    > The **ChangeCipherSpec** message is sent by both the client and the server to notify the receiving party that subsequent records will be protected under the newly negotiated **CipherSpec** and keys.¹⁶⁷

    The **CipherSpec** specifies the  **PRF**, the **bulk cipher algorithm** and the **MAC algorithm**.¹²⁷

7. **Finished** + **Application Data**:¹²⁶ᐟ¹⁶³ᐟ¹⁶⁹

    > A **Finished** message is always sent immediately after a change cipher spec message to verify that the key exchange and authentication processes were successful.¹⁶³

    The **Finished** message includes a `verify_data` value which is a value returned by the **PRF** with some parameters including a hash of all handshake messages and the **master secret**:¹⁶³

       struct {
       opaque verify_data[verify_data_length];
       } Finished;

       verify_data
          PRF(master_secret, finished_label, Hash(handshake_messages))
             [0..verify_data_length-1];        

    The **Finished** message is the first one protected using the newly negotiated **CipherSpec** and keys from the **key block**.¹⁶³ For all **bulk cipher algorithm**s, the content may be compressed using the negotiated **compression method** and encrypted using the **CipherSpec** keys.¹⁶⁸ᐟ¹⁴⁴

    Only the **Standard Stream Cipher** and **CBC Block Cipher** need the **MAC** (HMAC algorithm) of the **cipher suite**. The **MAC** is generated as:¹⁷⁰

       MAC(MAC_write_key, seq_num +
                             TLSCompressed.type +
                             TLSCompressed.version +
                             TLSCompressed.length +
                             TLSCompressed.fragment);

    No matter if compression is used, `TLSCompressed` is the structure which contains the data to be sent.¹⁶⁸ᐟ¹⁷⁰ Without going into detail about the structures in which the **Finished** message is embedded in `TLSCompressed`, it is sufficient to know that the **Finished** message is somehow part of the **content** field of the different **bulk cipher algorithm** structures:¹⁴⁴

    1. **Null** + **Standard Stream Cipher**:¹⁷⁰

        If the **cipher suite** is `TLS_NULL_WITH_NULL_NULL`, the message is sent as plaintext without a **MAC**.¹⁷⁰

        Regardless of the **bulk cipher algorithm**, if a **MAC** is specified, it is always computed on the complete plaintext (compressed or uncompressed) before encryption takes place.¹⁷⁰

        If just the **bulk cipher algorithm** is **Null**, the message is sent as plaintext with a **MAC**.¹⁷⁰

        Simplified, for **stream ciphers**, each digit is encrypted one at a time, therefore the ciphertext of the starting part can be calculated before the ciphertext of the trailing part.¹⁷¹

        For a **Standard Stream Cipher**, if set, the stream cipher encrypts the entire block, including the **MAC**:¹⁷⁰

           stream-ciphered struct {
               opaque content[TLSCompressed.length];
               opaque MAC[SecurityParameters.mac_length];
           } GenericStreamCipher;  

        E.g. `TLS_RSA_WITH_RC4_128_SHA`, specified in TLS 1.2 - RFC 5246 uses `RC4_128` **stream cipher** and `SHA` as the **MAC algorithm**.¹³⁰

    2. **CBC Block Cipher**:¹⁷²

        For **block ciphers**, the encryption includes operations with multiple data blocks with the same fixed length, therefore the whole plaintext that should be encrypted must be known to the actual encryption algorithm.¹⁷³ᐟ¹⁷² When the plaintext doesn't have a size that can directly be split into blocks, it will be padded.¹⁷²

        For **CBC Block Cipher**, just like before, the **MAC** is computed on the complete plaintext before encryption takes place.¹⁷⁰ The complete plaintext and the **MAC** together with padding information is *block-ciphered*.¹⁷² An additional unencrypted random **initialization vector (IV)** used by the **CBC Block Cipher** is sent as part of the block cipher message:¹⁷²

           struct {
               opaque IV[SecurityParameters.record_iv_length];
               block-ciphered struct {
                   opaque content[TLSCompressed.length];
                   opaque MAC[SecurityParameters.mac_length];
                   uint8 padding[GenericBlockCipher.padding_length];
                   uint8 padding_length;
               };
           } GenericBlockCipher;

        Most **chiper suite**s defined in TLS 1.2 - RFC 5246 are using **CBC Block Cipher** such as `TLS_RSA_WITH_AES_256_CBC_SHA`, which uses `AES_256_CBC` and `SHA` as the **MAC algorithm**.¹³⁰

    3. **AEAD**:¹⁷⁴

        **AEAD** stands for *Authenticated Encryption with Associated Data*.¹⁷⁵ **AEAD** functions provide a unified encryption and authentication operation which turns plaintext into authenticated ciphertext and back again sparing the necessity for a **MAC**.¹⁷⁵ᐟ¹⁷⁴

        > **AEAD** ciphers take as input a single key, a nonce, a plaintext, and "additional data" to be included in the authentication check...¹⁷⁴

        > A **nonce** is an arbitrary number that can be used just once... It is often a random or pseudo-random number issued in an authentication protocol to ensure that old communications cannot be reused in replay attacks.¹⁷⁶

        A part of a **nonce**, `nonce_explicit`, is sent together with the AEAD cipher message:¹⁷⁴

           struct {
              opaque nonce_explicit[SecurityParameters.record_iv_length];
              aead-ciphered struct {
                  opaque content[TLSCompressed.length];
              };
           } GenericAEADCipher;

        Suprisingly, no **AEAD** ciphers are specified in TLS 1.2 - RFC 5246 but were added to the **TLS Cipher Suite Registry** with **RFC 5288 - AES-GCM Cipher suites**.¹⁷⁷ᐟ¹⁷⁸

        One **AEAD** cipher defined in RFC 5288 is `TLS_DH_RSA_WITH_AES_128_GCM_SHA256`.¹⁷⁸ Suprisingly it specifies a **MAC algorithm** although **AEAD** ciphers do not need one.¹⁷⁴ This is because the **MAC algorithm** specifies the HMAC used for the PRF, either `SHA256` or `SHA384`.¹⁶²

    After the **Finished** message, the handshake is complete and the client and server may begin to exchange **Application Data**.¹⁶³

    Just like the **Finished** message, the **Application Data** may be compressed and is encrypted as shown above, using the negotiated **CipherSpec** and keys from the **key block**.¹⁶⁷

###6.2. TLS 1.2 Abbreviated Handshake

The **abbreviated handshake** is used to resume a previous session or duplicate an existing one:¹²⁶

    Client                                                Server

    ClientHello                   -------->
                                                      ServerHello
                                               (ChangeCipherSpec)
                                  <--------            [Finished]
    [ChangeCipherSpec]
    Finished                      -------->
    [Application Data]            <------->     [Application Data]

    [] Indicates messages protected using the negotiated bulk-data cipher
       (using the computed shared secret) and are protected by the negotiated MAC.
       The messages may also be compressed.

    () Indicates messages that do not belong to the Full Handshake but are
       necessary nonetheless.      

> The client sends a **ClientHello** using the Session ID (`session_id`) of the session to be resumed.  The server then checks its session cache for a match. If a match is found, and the server is willing to re-establish the connection under the specified session state, it will send a **ServerHello** with the same Session ID (`session_id`) value.  At this point, both client and server MUST send **ChangeCipherSpec** messages and proceed directly to **Finished** messages.  Once the re-establishment is complete, the client and server MAY begin to exchange application layer data. If a Session ID match is not found, the server generates a new session ID, and the TLS client and server perform a full handshake.¹²⁶

##7. TLS 1.3 in Detail

###7.1. TLS 1.3 Full Handshake

Again to start a TLS connection a **Full Handshake** is required:¹⁷⁹

           Client                                           Server

    Key  ^ ClientHello
    Exch | + key_share*
         | + signature_algorithms*
         | + psk_key_exchange_modes*
         v + pre_shared_key*       -------->
                                                      ServerHello  ^ Key
                                                     + key_share*  | Exch
                                                + pre_shared_key*  v
                                            {EncryptedExtensions}  ^  Server
                                            {CertificateRequest*}  v  Params
                                                   {Certificate*}  ^
                                             {CertificateVerify*}  | Auth
                                                       {Finished}  v
                                   <--------  [Application Data*]
         ^ {Certificate*}
    Auth | {CertificateVerify*}
         v {Finished}              -------->
           [Application Data]      <------->  [Application Data]

                  +  Indicates noteworthy extensions sent in the
                     previously noted message.

                  *  Indicates optional or situation-dependent
                     messages/extensions that are not always sent.

                  {} Indicates messages protected using keys
                     derived from a [sender]_handshake_traffic_secret.

                  [] Indicates messages protected using keys
                     derived from [sender]_application_traffic_secret_N.

No different to TLS 1.2, in TLS 1.3 a **Full Handshake** is required to initially negotiate the security parameters used to secure further **Application Data** messages.¹⁷⁹ᐟ¹⁸⁰

> Handshake messages MAY be coalesced into a single `TLSPlaintext` record or fragmented across several records.¹⁸¹

1. **ClientHello** + **ServerHello**:¹⁸⁰

    > When a client first connects to a server, it is REQUIRED to send the **ClientHello** as its first TLS message.¹⁸²

    Again the **ClientHello** includes supported security attributes from which the server will make selections to secure not only the **Application Data** but also handshake messages following the **ServerHello** message:¹⁷⁹ᐟ¹⁸³ᐟ¹⁸²ᐟ¹⁸⁴

    1. `Cipher Suite`:

        The **ClientHello** includes a list of **cipher suites** in preferred order.¹⁸² Again the cipher suite value is a 2 byte ID notated as a pair of hexadecimal numbers.¹⁸⁵ However **cipher suites** in TLS 1.3 only specify an **AEAD algorithm** and a **hash algorithm**.¹⁸⁵ The **key exchange algorithm** is negotiated separately via extensions.¹⁷⁹

        The **AEAD algorithm** encrypts and authenticates the message, so that a **MAC** is redundant.¹⁷⁵ᐟ¹⁸⁶

        The **hash algorithm** is used for the **key derivation function** (**KDF**), an algorithm used to generate the keys to protect further **Application Data**, which is an **HMAC-based Extract-and-Expand Key Derivation Function** (**HKDF**) in case of TLS 1.3.¹⁸⁷ᐟ¹⁸⁵ᐟ¹⁸⁸

        >     CipherSuite TLS_AEAD_HASH = VALUE;
        >    
        >     +-----------+------------------------------------------------+
        >     | Component | Contents                                       |
        >     +-----------+------------------------------------------------+
        >     | TLS       | The string "TLS"                               |
        >     |           |                                                |
        >     | AEAD      | The AEAD algorithm used for record protection  |
        >     |           |                                                |
        >     | HASH      | The hash algorithm used with HKDF              |
        >     |           |                                                |
        >     | VALUE     | The two-byte ID assigned for this cipher suite |
        >     +-----------+------------------------------------------------+
        >    
        > This specification defines the following cipher suites for use with TLS 1.3.
        >    
        >     x------------------------------+-------------+
        >     | Description                  | Value       |
        >     +------------------------------+-------------+
        >     | TLS_AES_128_GCM_SHA256       | {0x13,0x01} |
        >     |                              |             |
        >     | TLS_AES_256_GCM_SHA384       | {0x13,0x02} |
        >     |                              |             |
        >     | TLS_CHACHA20_POLY1305_SHA256 | {0x13,0x03} |
        >     |                              |             |
        >     | TLS_AES_128_CCM_SHA256       | {0x13,0x04} |
        >     |                              |             |
        >     | TLS_AES_128_CCM_8_SHA256     | {0x13,0x05} |
        >     +------------------------------+-------------+     

        At the time of this writing there are no updates for RFC 8446 - TLS 1.3, therefore all available cipher suites are the ones defined above.

        The server will select and set one of the provided **ClientHello** **cipher suite**s in its **ServerHello** message.¹⁸⁹ If it can not find a match it will respond with a `insufficient_security` **Alert** message, hence the **Full Handshake** can not proceed.¹⁹⁰ᐟ¹⁹¹

    2. `key_share` + `supported_groups`:

        The `key_share` extension is used to establish the **shared secret** of the used Diffie-Hellman Ephemeral group, either **Finite Field DH** or **Elleptic Curve DH**.¹⁹²

        For the **ClientHello** message, the extension includes a set of **public keys**, each for one of the groups specified in the `supported_groups` extension in the same order:¹⁹²

           struct {
               KeyShareEntry client_shares<0..2^16-1>;
           } KeyShareClientHello;

           struct {
               NamedGroup group;
               opaque key_exchange<1..2^16-1>;
           } KeyShareEntry;

        `group` corresponds to a group of the `supported_groups` and each `KeyShareEntry` must be in the same order as its corresponding `group` in the `supported_groups` extension.¹⁹²

        `key_exchange` includes the **public key** value `X` for **Finite Field DH** or the **public key** point with `X` and `Y` coordinates for **Elleptic Curve DH**.¹⁹³ᐟ¹⁹⁴ᐟ¹⁹⁵

        The server selects one **public key** by providing exactly one corresponding **public key** `KeyShareEntry` in its **ServerHello** `key_share` extension:¹⁹²

           struct {
               KeyShareEntry server_share;
           } KeyShareServerHello;

        The client identifies the used **public key** by the `group` of the servers selected `KeyShareEntry`.¹⁹²

        Regardless of the Diffie-Hellman group, both parties can calculate the **shared secret** once the **public keys** are shared.⁴⁴ᐟ¹⁹⁶

        The calculated **shared secret** is used as a parameter for the **HKDF** that is used to generate the protection keys for further messages.¹⁹⁷

        If the server can not find an overlap between the received `supported_groups` and the groups supported by the server, then the server will abort the handshake with a `handshake_failure` or `insufficient_security` **Alert** message.¹⁸³ᐟ¹⁹¹

    3. `psk_key_exchange_modes` + `pre_shared_key`:

        A **pre-shared key** (**PSK**) is a key that was shared before the handshake and is hold by the client and the server.¹⁹⁸ In case of TLS 1.3 it allows extra security and the possibility to send **zero round-trip time data** (**0-RTT data**).¹⁹⁹ᐟ¹⁹⁷

        If and how a **PSK** is used is determined by the `psk_key_exchange_modes` extension value sent in the **ClientHello** message.¹⁸³

           enum { psk_ke(0), psk_dhe_ke(1), (255) } PskKeyExchangeMode;

           struct {
               PskKeyExchangeMode ke_modes<1..255>;
           } PskKeyExchangeModes;

        Both the **DH shared secret** and the **PSK** are independently used as parameters for the **KDF**.¹⁹⁷ This is the reason why TLS 1.3 has three **key exchange algorithms**:¹⁷⁹

        -  **(EC)DHE** (Diffie-Hellman over either finite fields or elliptic
           curves):

           `key_share` and `supported_groups` extension are sent. The calculated **shared secret** builds the `DHE` parameter for the **KDF**. No `psk_key_exchange_modes` and `pre_shared_key` sent.¹⁸³ 0 is used for the **PSK** parameter of the **KDF**.¹⁹⁷

        -  **PSK-only**:

           `psk_key_exchange_modes` is sent with a value, which is used as the `psk_ke` parameter for the **KDF**.²⁰⁰ No `key_share` and `supported_groups` extension sent.²⁰⁰ᐟ²⁰¹ 0 is used for the **DHE** parameter of the **KDF**, therefore this method does not provide **forward secrey**.¹⁹⁷

        -  **PSK with (EC)DHE**:

           `psk_key_exchange_modes` is sent with value `psk_dhe_ke`.²⁰⁰ Both `key_share` and `supported_groups` as well as `psk_key_exchange_modes` and `pre_shared_key` extensions are provided.²⁰⁰ᐟ²⁰² The **KDF** will use a non-zero **PSK** value as well as a non-zero **DHE** value.¹⁹⁷

        Which **PSK** is used when the selected key exchange mode is either `psk_ke` or `psk_dhe_ke` is determined by the `pre_shared_key` extension which must be the last extension in the **ClientHello** and **ServerHello** message.²⁰³ When the client sends the `psk_key_exchange_modes` extension it must also send a `pre_shared_key` extension in the same **ClientHello** message.²⁰⁰

        The `pre_shared_key` extension is used to negotiate the identity of the **PSK**.²⁰³ Therefore the known **PSK** is selected by an identifier:²⁰³

           struct {
               opaque identity<1..2^16-1>;
               uint32 obfuscated_ticket_age;
           } PskIdentity;

           opaque PskBinderEntry<32..255>;

           struct {
               PskIdentity identities<7..2^16-1>;
               PskBinderEntry binders<33..2^16-1>;
           } OfferedPsks;

           struct {
               select (Handshake.msg_type) {
                   case client_hello: OfferedPsks;
                   case server_hello: uint16 selected_identity;
               };
           } PreSharedKeyExtension;

        The client provides one or more possible **PSK**s in its `pre_shared_key` extension which includes **PSK**-`identities` in its `OfferedPsks`.²⁰³ The server selects one of the provided **PSK**s by providing an **index** as the `selected_identity` in its `pre_shared_key` extension in the **ServerHello** message.²⁰³ The **index** matches the identity in the `identities` list provided by the client.²⁰³

        If the server can not identify any of the provided `identities`, it sends an `unknown_psk_identity` **Alert** message, hence the **Full Handshake** can not proceed.¹⁹⁰ᐟ¹⁹¹

    4. `signature_algorithms` + `signature_algorithms_cert`:

        The `signature_algorithms` and `signature_algorithms_cert` extensions have the same structure:²⁰⁴

           struct {
               SignatureScheme supported_signature_algorithms<2..2^16-2>;
           } SignatureSchemeList;   

        Both include a list of supported algorithms that are used for the digital signatures and are send in the **ClientHello** and **ServerHello** message if the client/server desires authentication via a certificate.²⁰⁴ `signature_algorithms_cert` contains the supported signatures in the certificate sent via the **Certificate** message.²⁰⁴

        `signature_algorithms` applies for signatures used in the **CertificateVerify** message and, if no `signature_algorithms_cert` extension is sent, also for the certificate sent via the **Certificate** message.²⁰⁴  Therefore at least the `signature_algorithms` extension is mandatory if the client/server desires authentication via a certificate.²⁰⁴

        > The keys found in certificates MUST also be of appropriate type for the signature algorithms they are used with.²⁰⁴

        > If a server is authenticating via a certificate and the client has not sent a "signature_algorithms" extension, then the server MUST abort the handshake with a "missing_extension" alert...²⁰⁴

        > If the server cannot produce a certificate chain that is signed only via the indicated supported algorithms, then it SHOULD continue the handshake by sending the client a certificate chain of its choice that may include algorithms that are not known to be supported by the client.²⁰⁵

        > If the client cannot construct an acceptable chain using the provided certificates and decides to abort the handshake, then it MUST abort the handshake with an appropriate certificate-related alert (by default, "unsupported_certificate"...²⁰⁵

    The client doesn't need to request OCSP responses with a `status_request` or `status_request_v2` extension because the server will send an OCSP response together with each certificate if it has some (see 3. **Certificate**).²¹² The `status_request_v2` is deprecated, the server only needs to send one OCSP response structurally referenced to the certificate.²¹²

    After the necessary serurity attributes have been negotiated via the **ClientHello** and **ServerHello** messages, the necessary encryption keys can get extracted by calling two defined functions `HKDF-Extract` and `Derive-Secret` in specific order using previously established `PSK` and `DHE` parameter values, which is depicted in the following **KDF** schedule:¹⁹⁷

                 0
                 |
                 v
       PSK ->  HKDF-Extract = Early Secret
                 |
                 +-----> Derive-Secret(., "ext binder" | "res binder", "")
                 |                     = binder_key
                 |
                 +-----> Derive-Secret(., "c e traffic", ClientHello)
                 |                     = client_early_traffic_secret
                 |
                 +-----> Derive-Secret(., "e exp master", ClientHello)
                 |                     = early_exporter_master_secret
                 v
           Derive-Secret(., "derived", "")
                 |
                 v
       (EC)DHE -> HKDF-Extract = Handshake Secret
                 |
                 +-----> Derive-Secret(., "c hs traffic",
                 |                     ClientHello...ServerHello)
                 |                     = client_handshake_traffic_secret
                 |
                 +-----> Derive-Secret(., "s hs traffic",
                 |                     ClientHello...ServerHello)
                 |                     = server_handshake_traffic_secret
                 v
           Derive-Secret(., "derived", "")
                 |
                 v
       0 -> HKDF-Extract = Master Secret
                 |
                 +-----> Derive-Secret(., "c ap traffic",
                 |                     ClientHello...server Finished)
                 |                     = client_application_traffic_secret_0
                 |
                 +-----> Derive-Secret(., "s ap traffic",
                 |                     ClientHello...server Finished)
                 |                     = server_application_traffic_secret_0
                 |
                 +-----> Derive-Secret(., "exp master",
                 |                     ClientHello...server Finished)
                 |                     = exporter_master_secret
                 |
                 +-----> Derive-Secret(., "res master",
                                       ClientHello...client Finished)
                                       = resumption_master_secret

    **HKDF** stands for **HMAC-based Extract-and-Expand Key Derivation Function (HKDF)** which is defined in **RFC 5869 - Extract-and-Expand HKDF**.²⁰⁶ `HKDF-Extract` is taking a `Salt` argument from the top and an argument from the left.¹⁹⁷ For the `Derive-Secret` function, the `Secret` argument is indicated by the incoming arrow and represented by a dot (`.`).¹⁹⁷

    Without going into detail about all function parameters and the actual function definitions of `HKDF-Extract` and `Derive-Secret`, the important part is, that the secrets are extracted from an ongoing sequence of function calls in which the shared input secret parameters `PSK` and `DHE` are used independently in separate steps:¹⁹⁷

    - `PSK` is the **pre-shared key** negotiated via the `pre_shared_key` extension.¹⁹⁷ᐟ¹⁸³

    - `(EC)DHE` is the calculated Diffie-Hellman shared secret which corresponding public keys got exchanged via the `key_share` extension.¹⁹⁷ᐟ¹⁸³ᐟ²⁰⁷

    Because TLS 1.3 allows the three key exchange modes (EC)DHE, PSK-only and PSK with (EC)DHE, one of the two input secrets `PSK` and `(EC)DHE` may not be available.¹⁷⁹ᐟ¹⁹⁷ In such a case a 0-value is used as function parameter.¹⁹⁷

    **Forward secrecy** is only provided when `(EC)DHE` is in use because the calculated secrets change with each session.²⁸

2. **EncryptedExtensions** + **CertificateRequest**:²⁰⁸

    The **EncryptedExtensions** message must be sent by the server immediately after the **ServerHello** and contains extensions that can be protected, therefore the **EncryptedExtensions** message is the first one encrypted using the negotiated **AEAD** encryption algorithm.²⁰⁹ The server may also send a **CertificateRequest** message after the **EncryptedExtensions** requesting authentication from the client.²¹⁰

    As mentioned before TLS 1.3 only uses **AEAD** encryption algorithms:¹⁸⁷ᐟ¹⁸⁶

       encrypted_record = AEAD-Encrypt(
           write_key,
           nonce,
           additional_data,
           plaintext
       )

       plaintext of encrypted_record = AEAD-Decrypt(
           peer_write_key,
           nonce,
           additional_data,
           encrypted_record
       )

    Most importantly the **AEAD** encryption and decryption algorithms take a key and a message to encrypt/decrypt.¹⁸⁶ The **EncryptedExtensions** and **CertificateRequest** messages are encrypted and decrypted using the `server_handshake_traffic_secret`.²⁰⁸ The `encrypted_record` is sent in a `TLSCiphertext` structure:¹⁸⁶

       struct {
           ContentType opaque_type = application_data; /* 23 */
           ProtocolVersion legacy_record_version = 0x0303; /* TLS v1.2 */
           uint16 length;
           opaque encrypted_record[TLSCiphertext.length];
       } TLSCiphertext;

    As seen above, TLS 1.3 does not include compression methods.¹⁸⁷

    > The client MUST check **EncryptedExtensions** for the presence of any forbidden extensions and if any are found MUST abort the handshake with an "illegal_parameter" alert.²⁰⁹

3. **Certificate**:

    The server must send a **Certificate** message whenever the agreed-upon key exchange method uses certificates for authentication, which is the case for all key exchange methods except **PSK**.¹⁷⁹ᐟ⁵⁴ The client sends a **Certificate** message if it was requested via a **CertificateRequest** message.¹⁷⁹ᐟ⁵⁴

    > If the server requests client authentication but no suitable certificate is available, the client MUST send a **Certificate** message containing no certificates:⁵⁴

       struct {
           opaque certificate_request_context<0..2^8-1>;
           CertificateEntry certificate_list<0..2^24-1>;
       } Certificate;

       struct {
           select (certificate_type) {
               case RawPublicKey:
                 /* From RFC 7250 ASN.1_subjectPublicKeyInfo */
                 opaque ASN1_subjectPublicKeyInfo<1..2^24-1>;

               case X509:
                 opaque cert_data<1..2^24-1>;
           };
           Extension extensions<0..2^16-1>;
       } CertificateEntry;

    `certificate_list` contains a sequence of `CertificateEntry` structures, each containing a single certificate together with some extensions.⁵⁴ The sequence represents the certificate chain and must start with the senders certificate and each following certificate verifies the previous one.⁵⁴ The root certificate as the trust anchor may be omitted.⁵⁴

    The server's `certificate_list` MUST always be non-empty.⁵⁴ If a client receives an empty **Certificate** message, it must abort the handshake with a `decode_error` alert message.²¹¹ A client must send an empty `certificate_list` if it doesn't have appropriate certificates.⁵⁴ In such a case the server decides if it wants to continue or abort.²¹¹

    The certificates in the `certificate_list` should conform to negotiated attributes:²⁰⁵ᐟ²¹⁴

    - The certificate type MUST be **X.509v3**, unless explicitly negotiated otherwise.²⁰⁵ᐟ²¹⁴

    - The certificate MUST allow the key to be used for signing with negotatied `signature_algorithms` and `signature_algorithms_cert`.²⁰⁵ᐟ²¹⁴

    - The `server_name` and `certificate_authorities` extension may narrow the selection of certificates.²⁰⁵ᐟ²¹⁴

    - Also the `status_request` extension may include an OCSP response in a **CertificateStatus** structure.²¹²

    While the server may send a certificate list of choice if it can not comply to the negotatiated restrictions, clients will not send certificates and abort the handshake with an `unsupported_certificate` **Alert** message.²⁰⁵

4. **CertificateVerify**:²¹⁵

    The **CertificateVerify** message must be send by server and client immediately after the **Certificate** message if such is provided and is used to provide proof that an endpoint possesses the private key corresponding to the provided end-entity certificate:²¹⁵

       struct {
           SignatureScheme algorithm;
           opaque signature<0..2^16-1>;
       } CertificateVerify;

    The algorithm field specifies the signature algorithm used.²¹⁵ The signature algorithm takes two parameters:²¹⁵

    1. The content covered by the digital signature, which is a hash value that consists of multiple parts including a special hash value of the **Certificate** message using a `Transcript-Hash` function.²¹⁵

    2. The private signing key corresponding to the end-entity certificate sent in the previous **Certificate** message.²¹⁵

    > If the CertificateVerify message is sent by a server, the signature
    algorithm MUST be one offered in the client's "signature_algorithms"
    extension unless no valid certificate chain can be produced without
    unsupported algorithms...²¹⁵

    > The receiver of a CertificateVerify message MUST verify the signature field. The verification process takes as input:²¹⁵
    >
    > -  The content covered by the digital signature²¹⁵
    >
    > -  The public key contained in the end-entity certificate found in
    >    the associated Certificate message²¹⁵
    >
    > -  The digital signature received in the signature field of the
    >    CertificateVerify message²¹⁵
    >
    > If the verification fails, the receiver MUST terminate the handshake with a "decrypt_error" alert.²¹⁵

5. **Finished**:²¹⁶

    The **Finished** message is the final message before **Application Data** can be sent.²¹⁶ It is essential for providing authentication of the handshake and of the computed keys.²¹⁶

       struct {
           opaque verify_data[Hash.length];
       } Finished;  

    The `verify_data` value is computed as follows:²¹⁶

       verify_data = HMAC(
           finished_key,
           Transcript-Hash(Handshake Context, Certificate*, CertificateVerify*)
       )

       * Only included if present.

    with:²¹⁶

       finished_key = HKDF-Expand-Label(BaseKey, "finished", "", Hash.length)

    > Recipients of **Finished** messages MUST verify that the contents are correct and if incorrect MUST terminate the connection with a "decrypt_error" alert.²¹⁶
    >
    > Once a side has sent its Finished message and has received and validated the Finished message from its peer, it may begin to send and receive Application Data over the connection.²¹⁶

6. **Application Data**:¹⁷⁹

    Once a side has received and validated the **Finished** message, it may begin to send and receive **Application Data**.²¹⁶ **Application Data** must be encrypted under the appropriate application traffic secret, therefore the sender has to send a **KeyUpdate** message indicating that it's updating its cryptographic keys.²¹⁶ᐟ²¹⁷

    The initial **KDF** as shown above also creates the first application traffic secrets `server_application_traffic_secret_0` and `client_application_traffic_secret_0`.¹⁹⁷ Each following application traffic secret is created using the `HKDF-Expand-Label` function:⁴⁷

        application_traffic_secret_N + 1 = HKDF-Expand-Label(
            application_traffic_secret_N,
            "traffic upd",
            "",
            Hash.length
        )

    How often a secret can be used is dependent on the amount of plaintext that can be send safely.²¹⁸ **TLS 1.3 - RFC 8446** therefore references the scientific paper **Limits on Authenticated Encryption Use in TLS**.²¹⁸ᐟ²¹⁹

###7.2. TLS 1.3 Session Resumption and PSK

Session tickets can be used to create and resume sessions:²⁸

           Client                                               Server

    Initial Handshake:
           ClientHello
           + key_share               -------->
                                                           ServerHello
                                                           + key_share
                                                 {EncryptedExtensions}
                                                 {CertificateRequest*}
                                                        {Certificate*}
                                                  {CertificateVerify*}
                                                            {Finished}
                                     <--------     [Application Data*]
           {Certificate*}
           {CertificateVerify*}
           {Finished}                -------->
                                     <--------      [NewSessionTicket]
           [Application Data]        <------->      [Application Data]


    Subsequent Handshake:
           ClientHello
           + key_share*
           + pre_shared_key          -------->
                                                           ServerHello
                                                      + pre_shared_key
                                                          + key_share*
                                                 {EncryptedExtensions}
                                                            {Finished}
                                     <--------     [Application Data*]
           {Finished}                -------->
           [Application Data]        <------->      [Application Data]

                  +  Indicates noteworthy extensions sent in the
                     previously noted message.

                  *  Indicates optional or situation-dependent
                     messages/extensions that are not always sent.

                  {} Indicates messages protected using keys
                     derived from a [sender]_handshake_traffic_secret.

                  [] Indicates messages protected using keys
                     derived from [sender]_application_traffic_secret_N.          

After the initial handshake is completed, the server can send the client a **NewSessionTicket** message:²²⁰

    struct {
        uint32 ticket_lifetime;
        uint32 ticket_age_add;
        opaque ticket_nonce<0..255>;
        opaque ticket<1..2^16-1>;
        Extension extensions<0..2^16-2>;
    } NewSessionTicket;

The `ticket_nonce` is a unique value across all tickets.²²⁰ After receiving the **NewSessionTicket** message, the PSK can be computed using the `ticket_nonce` and the `resumption_master_secret` (see [section 7.1. TLS 1.3 Full Handshake - 1.](#7.1.-tls-1.3-full-handshake)) as a parameter for the `HKDF-Expand-Label` function:²²⁰

    PSK = HKDF-Expand-Label(
        resumption_master_secret,
        "resumption",
        ticket_nonce,
        Hash.length
    )    

The unique `ticket` value is then used as the identity for the PSK in the `pre_shared_key` extension when the session gets resumed by the client.²²⁰

> If the server accepts the PSK, then the security context of the new connection is cryptographically tied to the original connection and the key derived from the initial handshake is used to bootstrap the cryptographic state instead of a full handshake.²⁸

> As the server is authenticating via a PSK, it does not send a **Certificate** or a **CertificateVerify** message.  When a client offers resumption via a PSK, it SHOULD also supply a "key_share" extension to the server to allow the server to decline resumption and fall back to a full handshake, if needed.²⁸

###7.3. TLS 1.3 0-RTT Data

Using a **PSK** also allows the client to send **zero round-trip time data** (**0-RTT data**) directly after the **ClientHello** message:¹⁸⁷ᐟ¹⁹⁹

    Client                                               Server

    ClientHello
    + early_data
    + key_share*
    + psk_key_exchange_modes
    + pre_shared_key
    (Application Data*)     -------->
                                                    ServerHello
                                               + pre_shared_key
                                                   + key_share*
                                          {EncryptedExtensions}
                                                  + early_data*
                                                     {Finished}
                            <--------       [Application Data*]
    (EndOfEarlyData)
    {Finished}              -------->
    [Application Data]      <------->        [Application Data]

          +  Indicates noteworthy extensions sent in the
             previously noted message.

          *  Indicates optional or situation-dependent
             messages/extensions that are not always sent.

          () Indicates messages protected using keys
             derived from a client_early_traffic_secret.

          {} Indicates messages protected using keys
             derived from a [sender]_handshake_traffic_secret.

          [] Indicates messages protected using keys
             derived from [sender]_application_traffic_secret_N.

The **Application Data** sent after the **ClientHello** message is encrypted using the `client_early_traffic_secret` which is extracted as one of the earliest secrets in the **KDF** sequence depicted in section [section 7.1.](#7.1.-tls-1.3-full-handshake).¹⁹⁹

##8. Getting a Certificate

To get an X.509 certificate, at first a **CA** or **RA** has to be selected depending on contract conditions and prices.⁸⁵

Once a **CA**/**RA** product to obtain a certificate has been selected various information have to be provided to the **CA**/**RA** such as name of business, business registration number or other identifying information including proof of identity.⁸⁵

The user is then required to generate a set of asymmetric keys and *use the private key to sign a **Certificate Signing Request** (**CSR**) which will contain the public key of the generated public-private pair among other information*.⁸⁵ There are plenty of instructions by various CA's on how to obtain a certificate including the creation of an appropriate **CSR**:²²¹

- [Comodo CSR Generation Instructions](https://support.comodo.com/index.php?/comodo/Knowledgebase/List/Index/19)
- [DigiCert CSR Generation Instructions](http://www.digicert.com/csr-creation.htm?rid=011592)
- [Entrust CSR Generation Instructions](https://www.entrustdatacard.com/knowledgebase/search?keyword=csr&productType=&serverType=)

The **CSR** is uploaded to the CA which uses the **CSR** and other information provided to create the user's X.509 certificate which typically has a validity period ranging from 1 to 3 years.⁸⁵ The end-entity certificate is signed with the private key corresponding to the CA's root certificate.⁸⁵ *The X.509 certificate is sent to the user using a variety of processes (FTP/HTTP/EMAIL)*.⁸⁵

Once the certificate is obtained, the web server TLS settings have to be changed to HTTPS which includes the provision of the acquired certificate or the certificate chain.²²²

##9. HTTP Strict Transport Security (HSTS)

**HTTP Strict Transport Security** (**HSTS**) is a web security policy to enforce that web browsers only use HTTPS when connecting to a specific website.²²⁴ The policy is implemented by the server supplying an `HTTP Strict-Transport-Security response header` in an HTTPS response:²²⁵

    Strict-Transport-Security: max-age=<expire-time>
    Strict-Transport-Security: max-age=<expire-time>; includeSubDomains
    Strict-Transport-Security: max-age=<expire-time>; preload

The `HSTS response header` is ignored in an HTTP response.²²⁶ This is because the initial request to the site may still be HTTP and an attacker may intercept the HTTP connection and inject the header or remove it.²²⁶

This limitation is addressed by an **HSTS preload service** maintained by Google.²²⁷ Browsers using the service include a hardcoded `HSTS preloaded list` of sites which support `HSTS` and requested the inclusion in the list.²²⁸ᐟ²⁴⁹

If the domain is in the `HSTS preloaded list`, the browser will directly use HTTPS to connect to the web server.²²⁷ Otherwise the browser will use HTTP and the web server should redirect to HTTPS.²²⁶ᐟ²²⁸

Both ways, the web server should include the `HSTS response header` in its HTTPS response.²²⁶ᐟ²²⁵ The browser will then evaluate the `max-age` and `includeSubDomains` directives:²²⁹

- `max-age=<expire-time>`:

    > The time, in seconds, that the browser should remember that a site is only to be accessed using HTTPS.²²⁹

- `includeSubDomains` Optional:

    > If this optional parameter is specified, this rule applies to all of the site's subdomains as well.²²⁹

The browser will then...

1. Automatically turn any insecure links referencing the web application into secure links.²²⁵

2. Terminate connection if the security of the connection cannot be ensured.²²⁵

3. Connect the site via HTTPS as long as the `max-age` is not expired.²²⁶

Every time the HTTPS response from the web server includes the `HSTS response header`, the saved time to remember the site in the browser is reset.²²⁶ In practice this means that the browser will never connect to the site via HTTP if it is requested at least once a year.

By including the optional `preload` directive in the `HSTS response header`, a site is requesting inclusion in the preload list and will automatically be included if it satisfies the necessary requirements:²³⁰

> 1. Serve a valid certificate.
>
> 2. Redirect from HTTP to HTTPS on the same host, if you are listening on port 80.
>
> 3. Serve all subdomains over HTTPS.
>
>    - In particular, you must support HTTPS for the www subdomain if a DNS record for that subdomain exists.
>
> 4. Serve an HSTS header on the base domain for HTTPS requests:
>
>    - The max-age must be at least 31536000 seconds (1 year).
>    - The includeSubDomains directive must be specified.
>    - The preload directive must be specified.
>    - If you are serving an additional redirect from your HTTPS site, that redirect must still have the HSTS header (rather than the page it > redirects to).

Although for a site to be added to the `HSTS preloaded list` is very simple, being removed is not:²³¹

> Be aware that inclusion in the preload list cannot easily be undone. Domains can be removed, but it takes months for a change to reach users with a Chrome update and we cannot make guarantees about other browsers. Don't request inclusion unless you're sure that you can support HTTPS for your entire site and all its subdomains in the long term.

---

## A. Glossary

AEAD - Authenticated Encryption with Associated Data

APNIC - Asia Pacific Network Information Centre

ASCII - American Standard Code for Information Interchange

ASN.1 - Abstract Syntax Notation One

BER - Basic Encoding Rules

CA - Certificate Authority

CMS - Cryptographic Message Syntax

CRL - Certificate Revocation List

CSR - Certificate Signing Request

CSS - Cascading Style Sheets

DER - Distinguished Encoding Rules

DH - Diffie-Hellman

DHCP - Dynamic Host Configuration Protocol

DN - Distinguished Name

DNS - Domain Name System

EC - Elliptic Curves

ECDHE = Elliptic Curve Diffie-Hellman Ephemeral

EE - End-Entity

HKDF - HMAC-based Extract-and-Expand Key Derivation Function

HSTS - HTTP Strict Transport Security

HTML - HyperText Markup Language

HTTP - Hypertext Transfer Protocol

HTTPS - Hypertext Transfer Protocol Secure

IANA - Internet Assigned Numbers Authority

ICMP - Internet Control Message Protocol

IP - Internet Protocol

ITU - International Telecommunications Union

IV - Initialization Vector

JS - JavaScript

KDF - Key Derivation Function

NDP - Neighbor Discovery Protocol

OCSP - Online Certificate Status Protocol

OID - Object Identifier

OS - Operating System

PDU - Protocol Data Unit

PEM - Privacy Enhanced Mail

PKCS - Public Key Cryptography Standards

PKI - Public Key Infrastructure

PRF - Pseudorandom Function

RA - Registration Authority

RDN - Relative Distinguished Name

RFC - Request for Comments

RSA - Rivest–Shamir–Adleman

SAN - Subject Alternative Name

SSL - Secure Sockets Layer

TCP - Transmission Control Protocol

TLS - Transport Layer Security

UDP - User Datagram Protocol

URL - Uniform Resource Locator

---

## B. Sources

1. [Wikipedia - HTTPS](https://en.wikipedia.org/wiki/HTTPS)

2. [Wikipedia - Internet](https://en.wikipedia.org/wiki/Internet#Internet_Protocol_Suite)

3. [Wikipedia - IP routing](https://en.wikipedia.org/wiki/IP_routing#Routing_algorithm)

4. [Wikipedia - IPv6](https://en.wikipedia.org/wiki/IPv6#Transition_mechanisms)

5. [Wikipedia - IPv4 address exhaustion](https://en.wikipedia.org/wiki/IPv4_address_exhaustion#Mitigation_efforts)

6. [Wikipedia - World population](https://en.wikipedia.org/wiki/World_population)

7. [MDN web docs - How the web works](https://developer.mozilla.org/en-US/docs/Learn/Getting_started_with_the_web/How_the_Web_works)

8. [Google Public DNS - Get Started](https://developers.google.com/speed/public-dns/docs/using)

9. [Graziani, Rick - IPv6 Fundamentals - Chapter 8 - Dynamic IPv4 Address Allocation](https://www.ciscopress.com/store/ipv6-fundamentals-a-straightforward-approach-to-understanding-9781587144776)

10. [Wikipedia - Neighbor Discovery Protocol](https://en.wikipedia.org/wiki/Neighbor_Discovery_Protocol)

11. [Wikipedia - Happy Eyeballs](https://en.wikipedia.org/wiki/Happy_Eyeballs)

12. [Wikipedia - Domain_Name_System](https://en.wikipedia.org/wiki/Domain_Name_System#DNS_message_format)

13. [Wikipedia - Transmission Control Protocol](https://en.wikipedia.org/wiki/Transmission_Control_Protocol#Connection_establishment)

14. [Wikipedia - Hypertext Transfer Protocol - HTTP session](https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol#HTTP_session)

15. [Wikipedia - Hypertext Transfer Protocol - Response message](https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol#Response_message)

16. [Wikipedia - List of HTTP status codes](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_Success)

17. [Wikipedia - HTML](https://en.wikipedia.org/wiki/HTML)

18. [Why Web Browser DNS Caching Can Be A Bad Thing](https://dyn.com/blog/web-browser-dns-caching-bad-thing/)

19. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - TLS/SSL Protocol](https://www.zytrax.com/tech/survival/ssl.html#ssl)

20. [Wikipedia - URL](https://en.wikipedia.org/wiki/URL)

21. [Wikipedia - Hostname](https://en.wikipedia.org/wiki/Hostname)

22. [MDN web docs - A typical HTTP session - Structure of a server response](https://developer.mozilla.org/en-US/docs/Web/HTTP/Session)

23. [Wikipedia - Transport Layer Security](https://en.wikipedia.org/wiki/Transport_Layer_Security#History_and_development)

24. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - Overview - Establishing a Secure Connection](https://www.zytrax.com/tech/survival/ssl.html#ssl)

25. [Stackoverflow - How does SSL/TLS work? - Answer by Thomas Pornin](https://security.stackexchange.com/a/20847)

26. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - TLS 1.3 Detailed Description](https://www.zytrax.com/tech/survival/ssl.html#tls-1-3-details)

27. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - TLS 1.2, TLS 1.1/SSL - Detailed Description](https://www.zytrax.com/tech/survival/ssl.html#tls-1-2-details)

28. [RFC 8446 - TLS 1.3 - 2.2. Resumption and Pre-Shared Key (PSK)](https://www.rfc-editor.org/rfc/rfc8446.html#section-2.2)

29. [RFC 5246 - TLS 1.2 - 7.3. Handshake Protocol Overview](https://www.rfc-editor.org/rfc/rfc5246#section-7.3)

30. [Wikipedia - Forward Secrecy](https://en.wikipedia.org/wiki/Forward_secrecy)

31. [Trend Micro - HTTPS Protocol Now Used in 58% of Phishing Websites](https://www.trendmicro.com/vinfo/hk-en/security/news/cybercrime-and-digital-threats/https-protocol-now-used-in-58-of-phishing-websites)

32. [Wikipedia - X.509 - Certificates](https://en.wikipedia.org/wiki/X.509#Certificates)

33. [Wikipedia - Digital signature](https://en.wikipedia.org/wiki/Digital_signature)

34. [Wikipedia - HTTPS - Browser integration](https://en.wikipedia.org/wiki/HTTPS#Browser_integration)

35. [RFC 8446 - TLS 1.3 - 6.1. Closure Alerts](https://www.rfc-editor.org/rfc/rfc8446.html#section-6.1)

36. [RFC 5246 - TLS 1.2 - 7.2.1. Closure Alerts](https://www.rfc-editor.org/rfc/rfc5246#section-7.2.1)

37. [Wikipedia - Encryption](https://en.wikipedia.org/wiki/Encryption)

38. [Wikipedia - Key (cryptography)](https://en.wikipedia.org/wiki/Key_(cryptography))

39. [Wikipedia - Key (cryptography) - Key size](https://en.wikipedia.org/wiki/Key_(cryptography)#Key_sizes)

40. [Wikipedia - Key (cryptography) - Key choice](https://en.wikipedia.org/wiki/Key_(cryptography)#Key_choice)

41. [Wikipedia - Computational hardness assumption](https://en.wikipedia.org/wiki/Computational_hardness_assumption)

42. [zytrax - Survival Guide - Encryption, Authentication - Symmetric Cryptography](https://www.zytrax.com/tech/survival/encryption.html#symmetric)

43. [zytrax - Survival Guide - Encryption, Authentication - Asymmetric Cryptography](https://www.zytrax.com/tech/survival/encryption.html#asymmetric)

44. [zytrax - Survival Guide - Encryption, Authentication - (Ephemeral) Diffie-Hellman Exchange](https://www.zytrax.com/tech/survival/encryption.html#dhe)

45. [Wikipedia - Session (computer science)](https://en.wikipedia.org/wiki/Session_(computer_science))

46. [RFC 5246 - TLS 1.2 - F.1.1.3. Diffie-Hellman Key Exchange with Authentication](https://www.rfc-editor.org/rfc/rfc5246#appendix-F.1.1.3)

47. [RFC 8446 - TLS 1.3 - 7.2. Updating Traffic Secrets](https://www.rfc-editor.org/rfc/rfc8446.html#section-7.2)

48. [zytrax - Survival Guide - Encryption, Authentication - Message Digests (Hashes)](https://www.zytrax.com/tech/survival/encryption.html#digests)

49. [Wikipedia - Cryptographic hash function](https://en.wikipedia.org/wiki/Cryptographic_hash_function)

50. [zytrax - Survival Guide - Encryption, Authentication - Message Authentication Code (MAC)](https://www.zytrax.com/tech/survival/encryption.html#mac)

51. [zytrax - Survival Guide - Encryption, Authentication - Digital Signatures](https://www.zytrax.com/tech/survival/encryption.html#signatures)

52. [Wikipedia - X.509 - Sample X.509 certificates - End-entity certificate](https://en.wikipedia.org/wiki/X.509#End-entity_certificate)

53. [RFC 5246 - TLS 1.2 - 7.4.2. Server Certificate](https://www.rfc-editor.org/rfc/rfc5246#section-7.4.2)

54. [RFC 8446 - TLS 1.3 - 4.4.2. Certificate](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.4.2)

55. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - X.509 (SSL) Certificate Overview](https://www.zytrax.com/tech/survival/ssl.html#x509-overview)

56. [RFC 5246 - TLS 1.2 - 7.4.7.1. RSA-Encrypted Premaster Secret Message](https://www.rfc-editor.org/rfc/rfc5246#section-7.4.7.1)

57. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - X.509 Certificate Format](https://www.zytrax.com/tech/survival/ssl.html#x509)

58. [Wikipedia - ASN.1](https://en.wikipedia.org/wiki/ASN.1)

59. [RFC 5912 - New ASN.1 for PKIX - 14. ASN.1 Module for RFC 5280, Explicit and Implicit](https://tools.ietf.org/html/rfc5912#section-14)

60. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1. Basic Certificate Fields](https://tools.ietf.org/html/rfc5280#section-4.1)

61. [Wikipedia - X.690](https://en.wikipedia.org/wiki/X.690)

62. [Wikipedia - X.690 - BER encoding](https://en.wikipedia.org/wiki/X.690#BER_encoding)

63. [Wikipedia - X.690 - DER encoding](https://en.wikipedia.org/wiki/X.690#DER_encoding)

64. [Wikipedia - ASN.1 - Example](https://en.wikipedia.org/wiki/ASN.1#Example)

65. [Wikipedia - Object identifier](https://en.wikipedia.org/wiki/Object_identifier)

66. [RFC 5280 - PKIX Certificate and CRL Profile - 4.2.1.3. Key Usage](https://tools.ietf.org/html/rfc5280#section-4.2.1.3)

67. [alvestrand.no - 2.5.29.15 - Key Usage](https://www.alvestrand.no/objectid/2.5.29.15.html)

68. [OID Repository (oid-info.com) - keyUsage(15)](http://oid-info.com/get/2.5.29.15)

69. [Wikipedia - X.509 - Certificates - Structure of a certificate](https://en.wikipedia.org/wiki/X.509#Structure_of_a_certificate)

70. [RFC 5246 - TLS 1.2 - 7.4.5. Server Hello Done](https://tools.ietf.org/html/rfc5246#section-7.4.5)

71. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - X.509 Certificate Usage](https://www.zytrax.com/tech/survival/ssl.html#x509-usage)

72. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1.2.4. Issuer](https://tools.ietf.org/html/rfc5280#section-4.1.2.4)

73. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1.2.3. Signature](https://tools.ietf.org/html/rfc5280#section-4.1.2.3)

74. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1.2.5. Validity](https://tools.ietf.org/html/rfc5280#section-4.1.2.5)

75. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1.2.6. Subject](https://tools.ietf.org/html/rfc5280#section-4.1.2.6)

76. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - X.509 Certificate Types and Terminology](https://www.zytrax.com/tech/survival/ssl.html#x509-terminology)

77. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1.2.7. Subject Public Key Info](https://tools.ietf.org/html/rfc5280#section-4.1.2.7)

78. [RFC 5246 - TLS 1.2 - 7.4.3. Server Key Exchange Message](https://www.rfc-editor.org/rfc/rfc5246#section-7.4.3)

79. [Vincent Bernat - TLS & Perfect Forward Secrecy - Diffie-Hellman with elliptic curves](https://vincent.bernat.ch/en/blog/2011-ssl-perfect-forward-secrecy#some-theory)

80. [RFC 5280 - PKIX Certificate and CRL Profile - 4.2.1.6. Subject Alternative Name](https://tools.ietf.org/html/rfc5280#section-4.2.1.6)

81. [RFC 5280 - PKIX Certificate and CRL Profile - 4.2.1.9. Basic Constraints](https://tools.ietf.org/html/rfc5280#section-4.2.1.9)

82. [Wikipedia - Certificate authority](https://en.wikipedia.org/wiki/Certificate_authority)

83. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1.1.3. signatureValue](https://tools.ietf.org/html/rfc5280#section-4.1.1.3)

84. [Wikipedia - X.509](https://en.wikipedia.org/wiki/X.509)

85. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - Process and Trust - CA's and X.509 Certificates](https://www.zytrax.com/tech/survival/ssl.html#trust)

86. [Information Security Stack Exchange - Why is the Signature Algorithm listed twice in an x509 Certificate? - Answer](https://security.stackexchange.com/a/115334/237416) by [BBerastegui](https://security.stackexchange.com/users/66134/bberastegui)

87. [RFC 6211 - CMS Algorithm Attribute - Abstract](https://tools.ietf.org/html/rfc6211)

88. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - X.509 Certificate Chaining](https://www.zytrax.com/tech/survival/ssl.html#x509-chaining)

89. [Wikipedia - X.509 - Certificate chains and cross-certification](https://en.wikipedia.org/wiki/X.509#Certificate_chains_and_cross-certification)

90. [Wikipedia - Chain of trust](https://en.wikipedia.org/wiki/Chain_of_trust)

91. [Wikipedia - X.509 - Sample X.509 certificates - Root certificate](https://en.wikipedia.org/wiki/X.509#Root_certificate)

92. [The Chromium Projects - Chromium - Chromium Security - Root Certificate Policy](https://sites.google.com/a/chromium.org/dev/Home/chromium-security/root-ca-policy)

93. [Wikipedia - Certificate authority - Overview](https://en.wikipedia.org/wiki/Certificate_authority#Overview)

94. [Mozilla Root Store Policy](https://www.mozilla.org/en-US/about/governance/policies/security-group/certs/policy/)

95. [Crowe FST Audit Kft. and Crowe FST Consulting Kft. - Webtrust Audit](https://www.crowe.com/hu/en-us/services/webtrust-audit)

96. [SSLShopper - What does the WebTrust program cover?](https://www.sslshopper.com/article-what-is-webtrust-for-cas-certification-authorities.html)

97. [WEBTRUST® FOR CERTIFICATION AUTHORITIES - 2.2 Certificate Policy (CP) Management (if applicable)](https://www.cpacanada.ca/-/media/site/operational/ms-member-services/docs/webtrust/principles-and-criteria-for-certification-authorities-v2-1.pdf)

98. [Wikipedia - Comodo Cybersecurity - Controversies - Certificate hacking](https://en.wikipedia.org/wiki/Comodo_Cybersecurity#Certificate_hacking)

99. [Wikipedia - DigiNotar](https://en.wikipedia.org/wiki/DigiNotar)

100. [Information Security Stack Exchange - Where are field names of decoded human readable X.509 certificates specified? - Answer](https://security.stackexchange.com/a/233883) by [mti2935](https://security.stackexchange.com/users/69717/mti2935)

101. [X.509 - Sample X.509 certificates - Intermediate certificate](https://en.wikipedia.org/wiki/X.509#Intermediate_certificate)

102. [Wikipedia - Public key infrastructure](https://en.wikipedia.org/wiki/Public_key_infrastructure)

103. [RFC 5280 - PKIX Certificate and CRL Profile - 4.1.1.2. signatureAlgorithm](https://tools.ietf.org/html/rfc5280#section-4.1.1.2)

104. [OID Repository (oid-info.com) - pkcs-1(1)](http://oid-info.com/get/1.2.840.113549.1.1)

105. [OID Repository (oid-info.com) - sha256WithRSAEncryption(11)](http://oid-info.com/get/1.2.840.113549.1.1.11)

106. [Wikipedia - RSA (cryptosystem)](https://en.wikipedia.org/wiki/RSA_(cryptosystem))

107. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - Certificate Bundles](https://www.zytrax.com/tech/survival/ssl.html#ca-bundles)

108. [X.509 - Certificates - Certificate filename extensions](https://en.wikipedia.org/wiki/X.509#Certificate_filename_extensions)

109. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - SSL Related File Format Notes](https://www.zytrax.com/tech/survival/ssl.html#formats)

110. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - SSL Related File Format Notes - PEM Format](https://www.zytrax.com/tech/survival/ssl.html#pem)

111. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - SSL Related File Format Notes - PEM BEGIN Keywords](https://www.zytrax.com/tech/survival/ssl.html#pem-ids)

112. [Wikipedia - PKCS](https://en.wikipedia.org/wiki/PKCS)

113. [Wikipedia - Cryptographic Message Syntax](https://en.wikipedia.org/wiki/Cryptographic_Message_Syntax)

114. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - SSL Related File Format Notes - File Extensions (Suffix)](https://www.zytrax.com/tech/survival/ssl.html#file-names)

115. [Wikipedia - PKCS 8](https://en.wikipedia.org/wiki/PKCS_8)

116. [Wikipedia - PKCS 12](https://en.wikipedia.org/wiki/PKCS_12)

117. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - Certificate Revocation Lists (CRLs)](https://www.zytrax.com/tech/survival/ssl.html#crls)

118. [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates - Online Certificate Status Protocol (OCSP)](https://www.zytrax.com/tech/survival/ssl.html#ocsp)

119. [Wikipedia - Online Certificate Status Protocol](https://en.wikipedia.org/wiki/Online_Certificate_Status_Protocol)

120. [RFC 6961 - TLS Multiple Certificate Status Extension](https://tools.ietf.org/html/rfc6961)

121. [Wikipedia - Certificate revocation list](https://en.wikipedia.org/wiki/Certificate_revocation_list)

122. [SSL.com - How Do Browsers Handle Revoked SSL/TLS Certificates?](https://www.ssl.com/article/how-do-browsers-handle-revoked-ssl-tls-certificates/)

123. [RFC 5246 - TLS 1.2 - 7.2.2. Error Alerts](https://tools.ietf.org/html/rfc5246#section-7.2.2)

124. [hashedout by The SSL Store - This is what happens when your SSL certificate expires - What happens when your SSL certificate expires?](https://www.thesslstore.com/blog/what-happens-when-your-ssl-certificate-expires/)

125. [Wikipedia - HMAC](https://en.wikipedia.org/wiki/HMAC)

126. [RFC 5246 - TLS 1.2 - 7.3. Handshake Protocol Overview](https://tools.ietf.org/html/rfc5246#section-7.3)

127. [RFC 5246 - TLS 1.2 - 7. The TLS Handshaking Protocols](https://tools.ietf.org/html/rfc5246#section-7)

128. [RFC 5246 - TLS 1.2 - 6.2.1. Fragmentation](https://tools.ietf.org/html/rfc5246#section-6.2.1)

129. [RFC 5246 - TLS 1.2 - 7.4.1.2. Client Hello](https://tools.ietf.org/html/rfc5246#section-7.4.1.2)

130. [RFC 5246 - TLS 1.2 - Appendix C. Cipher Suite Definitions](https://tools.ietf.org/html/rfc5246#appendix-C)

131. [RFC 5246 - TLS 1.2 - A.5. The Cipher Suite](https://tools.ietf.org/html/rfc5246#appendix-A.5)

132. [RFC 8422 - ECC Cipher Suites for TLS 1.2 - 6. Cipher Suites](https://tools.ietf.org/html/rfc8422#section-6)

133. [RFC 5246 - TLS 1.2 - 12. IANA Considerations](https://tools.ietf.org/html/rfc5246#section-12)

134. [Wikipedia - Internet Assigned Numbers Authority](https://en.wikipedia.org/wiki/Internet_Assigned_Numbers_Authority)

135. [iana.org - Transport Layer Security (TLS) Parameters - TLS Cipher Suites](https://www.iana.org/assignments/tls-parameters/tls-parameters.xhtml#tls-parameters-4)

136. [RFC 5246 - TLS 1.2 - 7.4.1.3. Server Hello](https://tools.ietf.org/html/rfc5246#section-7.4.1.3)

137. [RFC 5246 - TLS 1.2 - 7.4.1.4. Hello Extensions](https://tools.ietf.org/html/rfc5246#section-7.4.1.4)

138. [RFC 5246 - TLS 1.2 - 7.4.1.4.1. Signature Algorithms](https://tools.ietf.org/html/rfc5246#section-7.4.1.4.1)

139. [RFC 5246 - TLS 1.2 - 7.4.2. Server Certificate](https://tools.ietf.org/html/rfc5246#section-7.4.2)

140. [RFC 5246 - TLS 1.2 - 7.4.3. Server Key Exchange Message](https://tools.ietf.org/html/rfc5246#section-7.4.3)

141. [RFC 7924 - TLS 1.2 Cached Information Extension - 1. Introduction](https://tools.ietf.org/html/rfc7924#section-1)

142. [RFC 7924 - TLS 1.2 Cached Information Extension - 3. Cached Information Extension](https://tools.ietf.org/html/rfc7924#section-3)

143. [RFC 7924 - TLS 1.2 Cached Information Extension - 5. Fingerprint Calculation](https://tools.ietf.org/html/rfc7924#section-5)

144. [RFC 5246 - TLS 1.2 - 6.2.3. Record Payload Protection](https://tools.ietf.org/html/rfc5246#section-6.2.3)

145. [RFC 7924 - TLS 1.2 Cached Information Extension - 4.1. Server Certificate Message](https://tools.ietf.org/html/rfc7924#section-4.1)

146. [RFC 5246 - TLS 1.2 - 4.7. Cryptographic Attributes](https://tools.ietf.org/html/rfc5246#section-4.7)

147. [RFC 5246 - TLS 1.2 - 7.4.7. Client Key Exchange Message](https://tools.ietf.org/html/rfc5246#section-7.4.7)

148. [RFC 5246 - TLS 1.2 - 8.1. Computing the Master Secret](https://tools.ietf.org/html/rfc5246#section-8.1)

149. [RFC 5246 - TLS 1.2 - 6.3. Key Calculation](https://tools.ietf.org/html/rfc5246#section-6.3)

150. [RFC 5246 - TLS 1.2 - 7.4.7.1. RSA-Encrypted Premaster Secret Message](https://tools.ietf.org/html/rfc5246#section-7.4.7.1)

151. [RFC 5246 - TLS 1.2 - 7.4.7.2. Client Diffie-Hellman Public Value](https://tools.ietf.org/html/rfc5246#section-7.4.7.2)

152. [RFC 5246 - TLS 1.2 - 8.1.2. Diffie-Hellman](https://tools.ietf.org/html/rfc5246#section-8.1.2)

153. [RFC 5246 - TLS 1.2 - F.1.1.2. RSA Key Exchange and Authentication](https://tools.ietf.org/html/rfc5246#appendix-F.1.1.2)

154. [RFC 8422 - ECC Cipher Suites for TLS 1.2 - 1. Introduction](https://tools.ietf.org/html/rfc8422#section-1)

155. [RFC 8422 - ECC Cipher Suites for TLS 1.2 - 2. Key Exchange Algorithm](https://tools.ietf.org/html/rfc8422#section-2)

156. [RFC 8422 - ECC Cipher Suites for TLS 1.2 - 5.4. Server Key Exchange](https://tools.ietf.org/html/rfc8422#section-5.4)

157. [RFC 8422 - ECC Cipher Suites for TLS 1.2 - 5.7. Client Key Exchange](https://tools.ietf.org/html/rfc8422#section-5.7)

158. [RFC 8422 - ECC Cipher Suites for TLS 1.2 - 5.10. ECDH, ECDSA, and RSA Computations](https://tools.ietf.org/html/rfc8422#section-5.10)

159. [RFC 8422 - ECC Cipher Suites for TLS 1.2](https://tools.ietf.org/html/rfc8422)

160. [RFC 7627 - TLS 1.2 Session Hash Extension - 1. Introduction](https://www.rfc-editor.org/rfc/rfc7627#section-1)

161. [RFC 7627 - TLS 1.2 Session Hash Extension - 3. The TLS Session Hash](https://www.rfc-editor.org/rfc/rfc7627#section-3)

162. [RFC 5246 - TLS 1.2 - 5. HMAC and the Pseudorandom Function](https://tools.ietf.org/html/rfc5246#section-5)

163. [RFC 5246 - TLS 1.2 - 7.4.9. Finished](https://tools.ietf.org/html/rfc5246#section-7.4.9)

164. [RFC 5246 - TLS 1.2 - 7.4.4. Certificate Request](https://tools.ietf.org/html/rfc5246#section-7.4.4)

165. [RFC 5246 - TLS 1.2 - 7.4.6. Client Certificate](https://tools.ietf.org/html/rfc5246#section-7.4.6)

166. [RFC 5246 - TLS 1.2 - 7.4.8. Certificate Verify](https://tools.ietf.org/html/rfc5246#section-7.4.8)

167. [RFC 5246 - TLS 1.2 - 7.1. Change Cipher Spec Protocol](https://tools.ietf.org/html/rfc5246#section-7.1)

168. [RFC 5246 - TLS 1.2 - 6.2.2. Record Compression and Decompression](https://tools.ietf.org/html/rfc5246#section-6.2.2)

169. [RFC 5246 - TLS 1.2 - 6.2. Record Layer](https://tools.ietf.org/html/rfc5246#section-6.2)

170. [RFC 5246 - TLS 1.2 - 6.2.3.1. Null or Standard Stream Cipher](https://tools.ietf.org/html/rfc5246#section-6.2.3.1)

171. [Wikipedia - Stream cipher](https://en.wikipedia.org/wiki/Stream_cipher)

172. [RFC 5246 - TLS 1.2 - 6.2.3.2. CBC Block Cipher](https://tools.ietf.org/html/rfc5246#section-6.2.3.2)

173. [Wikipedia - Block cipher](https://en.wikipedia.org/wiki/Block_cipher)

174. [RFC 5246 - TLS 1.2 - 6.2.3.3. AEAD Ciphers](https://tools.ietf.org/html/rfc5246#section-6.2.3.3)

175. [Wikipedia - Authenticated encryption - Authenticated encryption with associated data (AEAD)](https://en.wikipedia.org/wiki/Authenticated_encryption#Authenticated_encryption_with_associated_data_(AEAD))

176. [Wikipedia - Cryptographic nonce](https://en.wikipedia.org/wiki/Cryptographic_nonce)

177. [RFC 5288 - AES-GCM Cipher suites - 1. Introduction](https://tools.ietf.org/html/rfc5288#section-1)

178. [RFC 5288 - AES-GCM Cipher suites - 3. AES-GCM Cipher Suites](https://tools.ietf.org/html/rfc5288#section-3)

179. [RFC 8446 - TLS 1.3 - 2. Protocol Overview](https://www.rfc-editor.org/rfc/rfc8446.html#section-2)

180. [RFC 8446 - TLS 1.3 - 4. Handshake Protocol](https://www.rfc-editor.org/rfc/rfc8446.html#section-4)

181. [RFC 8446 - TLS 1.3 - 5.1. Record Layer](https://www.rfc-editor.org/rfc/rfc8446.html#section-5.1)

182. [RFC 8446 - TLS 1.3 - 4.1.2. Client Hello](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.1.2)

183. [RFC 8446 - TLS 1.3 - 4.1.1. Cryptographic Negotation](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.1.1)

184. [RFC 8446 - TLS 1.3 - 4.2. Extensions](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2)

185. [RFC 8446 - TLS 1.3 - B.4. Cipher Suites](https://www.rfc-editor.org/rfc/rfc8446.html#appendix-B.4)

186. [RFC 8446 - TLS 1.3 - 5.2. Record Payload Protection](https://www.rfc-editor.org/rfc/rfc8446.html#section-5.2)

187. [RFC 8446 - TLS 1.3 - 1.2. Major Differences from TLS 1.2](https://www.rfc-editor.org/rfc/rfc8446.html#section-1.2)

188. [RFC 5869 - Extract-and-Expand HKDF - 1. Introduction](https://tools.ietf.org/html/rfc5869#section-1)

189. [RFC 8446 - TLS 1.3 - 4.1.3. Server Hello](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.1.3)

190. [RFC 8446 - TLS 1.3 - 6. Alert Protocol](https://www.rfc-editor.org/rfc/rfc8446.html#section-6)

191. [RFC 8446 - TLS 1.3 - 6.2. Error Alerts](https://www.rfc-editor.org/rfc/rfc8446.html#section-6.2)

192. [RFC 8446 - TLS 1.3 - 4.2.8. Key Share](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2.8)

193. [RFC 8446 - TLS 1.3 - 4.2.8.1. Diffie-Hellman Parameters](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2.8.1)

194. [RFC 8446 - TLS 1.3 - 4.2.8.2. ECDHE Parameters](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2.8.2)

195. [Navok, Svetlin - Practical Cryptography for Developers - Asymmetric Key Ciphers - Elliptic Curve Cryptography (ECC) - Private Key, Public Key and the Generator Point in ECC](https://cryptobook.nakov.com/asymmetric-key-ciphers/elliptic-curve-cryptography-ecc#private-key-public-key-and-the-generator-point-in-ecc)

196. [Navok, Svetlin - Practical Cryptography for Developers - Asymmetric Key Ciphers - ECDH Key Exchange](https://cryptobook.nakov.com/asymmetric-key-ciphers/ecdh-key-exchange)

197. [RFC 8446 - TLS 1.3 - 7.1. Key Schedule](https://www.rfc-editor.org/rfc/rfc8446.html#section-7.1)

198. [Wikipedia - Pre-shared key](https://en.wikipedia.org/wiki/Pre-shared_key)

199. [RFC 8446 - TLS 1.3 - 2.3. 0-RTT Data](https://www.rfc-editor.org/rfc/rfc8446.html#section-2.3)

200. [RFC 8446 - TLS 1.3 - 4.2.9. Pre-Shared Key Exchange Modes](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2.9)

201. [RFC 8446 - TLS 1.3 - 9.2. Mandatory-to-Implement Extensions](https://www.rfc-editor.org/rfc/rfc8446.html#section-9.2)

202. [RFC 8446 - TLS 1.3 - 4.2.7. Supported Groups](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2.7)

203. [RFC 8446 - TLS 1.3 - 4.2.11. Pre-Shared Key Extension](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2.11)

204. [RFC 8446 - TLS 1.3 - 4.2.3. Signature Algorithms](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.2.3)

205. [RFC 8446 - TLS 1.3 - 4.4.2.2. Server Certificate Selection](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.4.2.2)

206. [RFC 5869 - Extract-and-Expand HKDF](https://tools.ietf.org/html/rfc5869)

207. [RFC 8446 - TLS 1.3 - 7.4. (EC)DHE Shared Secret Calculation](https://www.rfc-editor.org/rfc/rfc8446.html#section-7.4)

208. [RFC 8446 - TLS 1.3 - 4.3. Server Parameters](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.3)

209. [RFC 8446 - TLS 1.3 - 4.3.1. Encrypted Extensions](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.3.1)

210. [RFC 8446 - TLS 1.3 - 4.3.2. Certificate Request](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.3.2)

211. [RFC 8446 - TLS 1.3 - 4.4.2.4. Receiving a Certificate Message](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.4.2.4)

212. [RFC 8446 - TLS 1.3 - 4.4.2.1. OCSP Status and SCT Extensions](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.4.2.1)

213. [Wikipedia - OCSP stapling - Solution](https://en.wikipedia.org/wiki/OCSP_stapling#Solution)

214. [RFC 8446 - TLS 1.3 - 4.4.2.3. Client Certificate Selection](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.4.2.3)

215. [RFC 8446 - TLS 1.3 - 4.4.3. Certificate Verify](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.4.3)

216. [RFC 8446 - TLS 1.3 - 4.4.4. Finished](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.4.4)

217. [RFC 8446 - TLS 1.3 - 4.6.3. Key and Initialization Vector Update](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.6.3)

218. [RFC 8446 - TLS 1.3 - 5.5. Limits on Key Usage](https://www.rfc-editor.org/rfc/rfc8446.html#section-5.5)

219. [Luykx, Atul - Paterson, Kenneth G. - Limits on Authenticated Encryption Use in TLS](https://www.isg.rhul.ac.uk/~kp/TLS-AEbounds.pdf)

220. [RFC 8446 - TLS 1.3 - 4.6.1. New Session Ticket Message](https://www.rfc-editor.org/rfc/rfc8446.html#section-4.6.1)

221. [SSL Shopper - What is a CSR (Certificate Signing Request)?](https://www.sslshopper.com/what-is-a-csr-certificate-signing-request.html)

222. [GEEKFLARE - Kumar, Chandan - How to Setup Apache HTTP with SSL](https://geekflare.com/apache-setup-ssl-certificate/)

223. [Wikipedia - Forward Secrecy - Definition](https://en.wikipedia.org/wiki/Forward_secrecy#Definition)

224. [Wikipedia - HTTP Strict Transport Security](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security)

225. [Wikipedia - HTTP Strict Transport Security - HSTS mechanism overview](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security#HSTS_mechanism_overview)

226. [MDN web docs - HTTP - HTTP headers - Strict-Transport-Security - Description](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security#Description)

227. [MDN web docs - HTTP - HTTP headers - Strict-Transport-Security - Preloading Strict Transport Security](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security#Preloading_Strict_Transport_Security)

228. [Wikipedia - HTTP Strict Transport Security - Limitations](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security#Limitations)

229. [MDN web docs - HTTP - HTTP headers - Strict-Transport-Security - Directives](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security#Directives)

230. [hstspreload.org - Submission Requirements](https://hstspreload.org/#submission-requirements)

231. [hstspreload.org - Removal](https://hstspreload.org/#removal)

232. [GlobalSign Support - Root & Intermediate Certificate Bundles](https://support.globalsign.com/ca-certificates/root-certificates/root-intermediate-certificate-bundles)

233. [NGINX - Configuring HTTPS servers - SSL certificate chains](http://nginx.org/en/docs/http/configuring_https_servers.html#chains)

234. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 2. Protocol Overview](https://tools.ietf.org/html/rfc6960#section-2)

235. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 3.1. Certificate Content](https://tools.ietf.org/html/rfc6960#section-3.1)

236. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 2.1. Request](https://tools.ietf.org/html/rfc6960#section-2.1)

237. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 4.1.1. ASN.1 Specification of the OCSP Request](https://tools.ietf.org/html/rfc6960#section-4.1.1)

238. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 2.2. Response](https://tools.ietf.org/html/rfc6960#section-2.2)

239. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 4.2.1. ASN.1 Specification of the OCSP Response](https://tools.ietf.org/html/rfc6960#section-4.2.1)

240. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 3.2. Signed Response Acceptance Requirements](https://tools.ietf.org/html/rfc6960#section-3.2)

241. [RFC 6961 - TLS Multiple Certificate Status Extension - 1. Introduction](https://tools.ietf.org/html/rfc6961#section-1)

242. [RFC 6066 - TLS Extensions: Extension Definitions - Abstract](https://tools.ietf.org/html/rfc6066)

243. [RFC 6066 - TLS Extensions: Extension Definitions - 8. Certificate Status Request](https://tools.ietf.org/html/rfc6066#section-8)

244. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 4.2.2.2. Authorized Responders](https://tools.ietf.org/html/rfc6960#section-4.2.2.2)

245. [RFC 6960 - PKIX Online Certificate Status Protocol - OCSP - 4.2.2.3. Basic Response](https://tools.ietf.org/html/rfc6960#section-4.2.2.3)

246. [RFC 6961 - TLS Multiple Certificate Status Extension - 2.2. Multiple Certificate Status Request Record](https://tools.ietf.org/html/rfc6961#section-2.2)

247. [RFC 4366 - TLS Extensions](https://tools.ietf.org/html/rfc4366)

248. [Chung, Taejoong - APNIC - Is the web ready for OCSP Must-Staple?](https://blog.apnic.net/2019/01/15/is-the-web-ready-for-ocsp-must-staple)

249. [hstspreload.org - Information](https://hstspreload.org/#information)

250. [RFC 5246 - TLS 1.2 - F.1.1.3. Diffie-Hellman Key Exchange with Authentication](https://tools.ietf.org/html/rfc5246#appendix-F.1.1.3)

<!-- ⁰¹²³⁴⁵⁶⁷⁸⁹ᐟ -->

---

## C. References

- [alvestrand.no - 2.5.29.15](https://www.alvestrand.no/objectid/2.5.29.15.html)

- [Chung, Taejoong - APNIC - Is the web ready for OCSP Must-Staple?](https://blog.apnic.net/2019/01/15/is-the-web-ready-for-ocsp-must-staple)

- [Crowe FST Audit Kft. and Crowe FST Consulting Kft. - Webtrust Audit](https://www.crowe.com/hu/en-us/services/webtrust-audit)

- [GEEKFLARE - Kumar, Chandan - How to Setup Apache HTTP with SSL](https://geekflare.com/apache-setup-ssl-certificate/)

- [GlobalSign Support - Root & Intermediate Certificate Bundles](https://support.globalsign.com/ca-certificates/root-certificates/root-intermediate-certificate-bundles)

- [Google Public DNS - Get Started](https://developers.google.com/speed/public-dns/docs/using)

- [Graziani, Rick - IPv6 Fundamentals](https://www.ciscopress.com/store/ipv6-fundamentals-a-straightforward-approach-to-understanding-9781587144776)

- [hashedout by The SSL Store - This is what happens when your SSL certificate expires - What happens when your SSL certificate expires?](https://www.thesslstore.com/blog/what-happens-when-your-ssl-certificate-expires/)

- [hstspreload.org](https://hstspreload.org/)

- [iana.org - Transport Layer Security (TLS) Parameters](https://www.iana.org/assignments/tls-parameters/tls-parameters.xhtml)

- [Information Security Stack Exchange - Where are field names of decoded human readable X.509 certificates specified?](https://security.stackexchange.com/a/233883)

- [Information Security Stack Exchange - Why is the Signature Algorithm listed twice in an x509 Certificate?](https://security.stackexchange.com/a/115334/237416)

- [Luykx, Atul - Paterson, Kenneth G. - Limits on Authenticated Encryption Use in TLS](https://www.isg.rhul.ac.uk/~kp/TLS-AEbounds.pdf)

- [MDN web docs - HTTP](https://developer.mozilla.org/en-US/docs/Web/HTTP)

- [MDN web docs - How the web works](https://developer.mozilla.org/en-US/docs/Learn/Getting_started_with_the_web/How_the_Web_works)

- [Mozilla Root Store Policy](https://www.mozilla.org/en-US/about/governance/policies/security-group/certs/policy/)

- [Navok, Svetlin - Practical Cryptography for Developers](https://cryptobook.nakov.com)

- [NGINX - Configuring HTTPS servers - SSL certificate chains](http://nginx.org/en/docs/http/configuring_https_servers.html)

- [OID Repository (oid-info.com)](http://oid-info.com)

- [RFC 4366 - TLS Extensions](https://tools.ietf.org/html/rfc4366)

- [RFC 5246 - TLS 1.2](https://www.rfc-editor.org/rfc/rfc5246)

- [RFC 5280 - PKIX Certificate and CRL Profile](https://tools.ietf.org/html/rfc5280)

- [RFC 5288 - AES-GCM Cipher suites](https://tools.ietf.org/html/rfc5288)

- [RFC 5869 - Extract-and-Expand HKDF](https://tools.ietf.org/html/rfc5869)

- [RFC 5912 - New ASN.1 for PKIX](https://tools.ietf.org/html/rfc5912)

- [RFC 6066 - TLS Extensions: Extension Definitions](https://tools.ietf.org/html/rfc6066)

- [RFC 6211 - CMS Algorithm Attribute](https://tools.ietf.org/html/rfc6211)

- [RFC 6960 - PKIX Online Certificate Status Protocol](https://tools.ietf.org/html/rfc6960)

- [RFC 6961 - TLS Multiple Certificate Status Extension](https://tools.ietf.org/html/rfc6961)

- [RFC 7627 - TLS 1.2 Session Hash Extension](https://www.rfc-editor.org/rfc/rfc7627)

- [RFC 7924 - TLS 1.2 Cached Information Extension](https://tools.ietf.org/html/rfc7924)

- [RFC 8422 - ECC Cipher Suites for TLS 1.2](https://tools.ietf.org/html/rfc8422)

- [RFC 8446 - TLS 1.3](https://www.rfc-editor.org/rfc/rfc8446.html)

- [SSL.com - How Do Browsers Handle Revoked SSL/TLS Certificates?](https://www.ssl.com/article/how-do-browsers-handle-revoked-ssl-tls-certificates/)

- [SSL Shopper - What does the WebTrust program cover?](https://www.sslshopper.com/article-what-is-webtrust-for-cas-certification-authorities.html)

- [SSL Shopper - What is a CSR (Certificate Signing Request)?](https://www.sslshopper.com/what-is-a-csr-certificate-signing-request.html)

- [Stackoverflow - How does SSL/TLS work?](https://security.stackexchange.com/a/20847)

- [The Chromium Projects - Chromium](https://sites.google.com/a/chromium.org/dev/)

- [Trend Micro - HTTPS Protocol Now Used in 58% of Phishing Websites](https://www.trendmicro.com/vinfo/hk-en/security/news/cybercrime-and-digital-threats/https-protocol-now-used-in-58-of-phishing-websites)

- [Vincent Bernat - TLS & Perfect Forward Secrecy - Diffie-Hellman with elliptic curves](https://vincent.bernat.ch/en/blog/2011-ssl-perfect-forward-secrecy)

- [WEBTRUST® FOR CERTIFICATION AUTHORITIES](https://www.cpacanada.ca/-/media/site/operational/ms-member-services/docs/webtrust/principles-and-criteria-for-certification-authorities-v2-1.pdf)

- [Why Web Browser DNS Caching Can Be A Bad Thing](https://dyn.com/blog/web-browser-dns-caching-bad-thing/)

- [Wikipedia - ASN.1](https://en.wikipedia.org/wiki/ASN.1)

- [Wikipedia - Authenticated encryption](https://en.wikipedia.org/wiki/Authenticated_encryption)

- [Wikipedia - Block cipher](https://en.wikipedia.org/wiki/Block_cipher)

- [Wikipedia - Certificate authority](https://en.wikipedia.org/wiki/Certificate_authority)

- [Wikipedia - Certificate revocation list](https://en.wikipedia.org/wiki/Certificate_revocation_list)

- [Wikipedia - Chain of trust](https://en.wikipedia.org/wiki/Chain_of_trust)

- [Wikipedia - Comodo Cybersecurity](https://en.wikipedia.org/wiki/Comodo_Cybersecurity)

- [Wikipedia - Computational hardness assumption](https://en.wikipedia.org/wiki/Computational_hardness_assumption)

- [Wikipedia - Cryptographic hash function](https://en.wikipedia.org/wiki/Cryptographic_hash_function)

- [Wikipedia - Cryptographic Message Syntax](https://en.wikipedia.org/wiki/Cryptographic_Message_Syntax)

- [Wikipedia - Cryptographic nonce](https://en.wikipedia.org/wiki/Cryptographic_nonce)

- [Wikipedia - DigiNotar](https://en.wikipedia.org/wiki/DigiNotar)

- [Wikipedia - Digital signature](https://en.wikipedia.org/wiki/Digital_signature)

- [Wikipedia - Domain_Name_System](https://en.wikipedia.org/wiki/Domain_Name_System)

- [Wikipedia - Encryption](https://en.wikipedia.org/wiki/Encryption)

- [Wikipedia - Forward Secrecy](https://en.wikipedia.org/wiki/Forward_secrecy)

- [Wikipedia - Happy Eyeballs](https://en.wikipedia.org/wiki/Happy_Eyeballs)

- [Wikipedia - HMAC](https://en.wikipedia.org/wiki/HMAC)

- [Wikipedia - Hostname](https://en.wikipedia.org/wiki/Hostname)

- [Wikipedia - HTML](https://en.wikipedia.org/wiki/HTML)

- [Wikipedia - HTTP Strict Transport Security](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security)

- [Wikipedia - HTTPS](https://en.wikipedia.org/wiki/HTTPS)

- [Wikipedia - Hypertext Transfer Protocol](https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol)

- [Wikipedia - Internet Assigned Numbers Authority](https://en.wikipedia.org/wiki/Internet_Assigned_Numbers_Authority)

- [Wikipedia - Internet](https://en.wikipedia.org/wiki/Internet)

- [Wikipedia - IP routing](https://en.wikipedia.org/wiki/IP_routing)

- [Wikipedia - IPv4 address exhaustion](https://en.wikipedia.org/wiki/IPv4_address_exhaustion)

- [Wikipedia - IPv6](https://en.wikipedia.org/wiki/IPv6)

- [Wikipedia - Key (cryptography)](https://en.wikipedia.org/wiki/Key_(cryptography))

- [Wikipedia - List of HTTP status codes](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)

- [Wikipedia - Neighbor Discovery Protocol](https://en.wikipedia.org/wiki/Neighbor_Discovery_Protocol)

- [Wikipedia - Object identifier](https://en.wikipedia.org/wiki/Object_identifier)

- [Wikipedia - OCSP stapling - Solution](https://en.wikipedia.org/wiki/OCSP_stapling)

- [Wikipedia - Online Certificate Status Protocol](https://en.wikipedia.org/wiki/Online_Certificate_Status_Protocol)

- [Wikipedia - PKCS 8](https://en.wikipedia.org/wiki/PKCS_8)

- [Wikipedia - PKCS 12](https://en.wikipedia.org/wiki/PKCS_12)

- [Wikipedia - PKCS](https://en.wikipedia.org/wiki/PKCS)

- [Wikipedia - Pre-shared key](https://en.wikipedia.org/wiki/Pre-shared_key)

- [Wikipedia - Public key infrastructure](https://en.wikipedia.org/wiki/Public_key_infrastructure)

- [Wikipedia - RSA (cryptosystem)](https://en.wikipedia.org/wiki/RSA_(cryptosystem))

- [Wikipedia - Session (computer science)](https://en.wikipedia.org/wiki/Session_(computer_science))

- [Wikipedia - Stream cipher](https://en.wikipedia.org/wiki/Stream_cipher)

- [Wikipedia - Transmission Control Protocol](https://en.wikipedia.org/wiki/Transmission_Control_Protocol)

- [Wikipedia - Transport Layer Security](https://en.wikipedia.org/wiki/Transport_Layer_Security)

- [Wikipedia - URL](https://en.wikipedia.org/wiki/URL)

- [Wikipedia - World population](https://en.wikipedia.org/wiki/World_population)

- [Wikipedia - X.509](https://en.wikipedia.org/wiki/X.509)

- [Wikipedia - X.690](https://en.wikipedia.org/wiki/X.690)

- [zytrax - Survival Guide - Encryption, Authentication](https://www.zytrax.com/tech/survival/encryption.html)

- [zytrax - Survival guides - TLS/SSL and SSL (X.509) Certificates](https://www.zytrax.com/tech/survival/ssl.html)

<!-- TODOY: anchor überprüfen -->
