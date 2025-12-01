# Chirp — Package Specification

> **Cluster:** `content`
> **Language:** `php`
> **Milestone:** `m5`
> **Repo:** `https://github.com/decodelabs/chirp`
> **Role:** Tweet parser

This document describes the purpose, contracts, and design of **Chirp** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Chirp in their own applications or libraries.
- Contributors **maintaining or extending** Chirp.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Chirp provides a tweet parser that converts plain text tweet content into HTML markup. It identifies and converts Twitter/X-specific elements including:

- URLs (with automatic protocol detection)
- Hashtags (converted to links)
- Usernames and mentions (including list mentions)
- Reply indicators

The parser outputs HTML wrapped in a `Markup` interface from the Tagged library, ensuring proper handling in all rendering contexts.

### 1.2 Non-Goals

Chirp does **not**:

- Fetch tweets from the Twitter/X API — it only parses text content
- Handle Twitter/X API authentication or rate limiting
- Provide tweet embedding or rich media display
- Parse Twitter/X JSON data structures — it works with plain text strings
- Handle Twitter Cards or other metadata formats
- Provide tweet composition or editing features

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `content` (see Chorus taxonomy)
- Chirp is a specialized content transformation package that focuses specifically on Twitter/X tweet text parsing. It sits in the content cluster alongside other text processing and markup generation tools. It depends on Tagged for HTML generation and integrates with Metamorph for use in content transformation pipelines.

### 2.2 Typical Usage Contexts

Typical places Chirp appears:

- Rendering imported tweets on websites
- Displaying tweet content in blog posts or articles
- Converting tweet text for archival or display purposes
- Content transformation pipelines via Metamorph integration
- Social media content management systems

Chirp is intended to be used whenever an application needs to convert raw tweet text into properly formatted HTML with clickable links for URLs, hashtags, and mentions.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Chirp\Parser`
  Main parser class that converts tweet text into HTML markup. Provides a single `parse()` method that takes a string and returns a `Markup` object.

- `DecodeLabs\Metamorph\Handler\Tweet`
  Metamorph handler implementation that allows Chirp to be used via the Metamorph simplified interface. Detected at runtime if Metamorph is installed, used for content transformation.

### 3.2 Main Entry Points

The main usage pattern is through the `Parser` class:

```php
use DecodeLabs\Chirp\Parser;

$parser = new Parser();
$markup = $parser->parse($tweetText);
```

Alternatively, when Metamorph is available:

```php
use DecodeLabs\Metamorph;

$markup = Metamorph::tweet($tweetText);
```

---

## 4. Dependencies

### 4.1 Decode Labs

- `decodelabs/tagged` (required)
  Used for HTML markup generation. The parser returns `Markup` objects from Tagged, ensuring proper output handling in all rendering contexts.

### 4.2 External

- None

### 4.3 Optional Integrations

- `decodelabs/metamorph` (optional)
  Detected at runtime if installed, used for simplified content transformation interface. Provides the `Tweet` handler class that wraps the `Parser` for use in Metamorph pipelines.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- The parser always returns `null` for empty or null input strings
- All output is properly HTML-escaped before processing
- URLs are always opened in new tabs (`target="_blank"`) with `rel="external nofollow"`
- Hashtag links point to Twitter/X search URLs
- Username links point to Twitter/X profile URLs
- List mentions are properly identified and linked

### 5.2 Input & Output Contracts

**Input:**
- `Parser::parse(?string $text): ?Markup`
  - Accepts a nullable string containing tweet text
  - Returns `null` if input is empty or null
  - Returns a `Markup` object containing the parsed HTML

**Processing Order:**
1. HTML escaping of input text
2. URL detection and conversion
3. Hashtag detection and conversion
4. Username/mention detection and conversion

**Output:**
- Returns a `DecodeLabs\Tagged\Markup` interface implementation
- All links are wrapped in `<a>` elements with appropriate attributes
- Links have CSS classes applied (`url`, `hashtag`, `user`, `list`)

### 5.3 URL Processing

- Detects URLs with or without protocols
- Automatically adds `https://` protocol if missing
- Validates domain TLDs against common patterns
- Preserves original URL text in link content

### 5.4 Hashtag Processing

- Detects hashtags starting with `#` or `＃` (full-width)
- Converts to links pointing to Twitter/X search
- Preserves the hashtag symbol in the link text

### 5.5 Username Processing

- Detects usernames starting with `@` or `＠` (full-width)
- Handles both regular mentions and list mentions (e.g., `@username/listname`)
- Converts to links pointing to Twitter/X profiles or lists
- Applies appropriate CSS classes (`user` or `list`)

---

## 6. Error Handling

- The parser does not throw exceptions for invalid input
- Empty or null input returns `null` gracefully
- Invalid URLs that don't match the pattern are left unchanged
- Regex processing failures fall back to returning the original text
- All HTML escaping uses PHP's built-in `htmlspecialchars()` with `ENT_QUOTES` flag

---

## 7. Configuration & Extensibility

- The parser is not currently configurable
- Processing order and regex patterns are hardcoded
- CSS classes applied to links are fixed
- Base URL for Twitter/X links is hardcoded to `https://x.com/`

<!-- TODO: consider making base URL, CSS classes, and processing order configurable -->

---

## 8. Interactions with Other Packages

### 8.1 Tagged

Chirp depends on Tagged for HTML generation. The parser uses:
- `DecodeLabs\Tagged\Element` for creating link elements
- `DecodeLabs\Tagged\Buffer` for wrapping the final HTML output
- `DecodeLabs\Tagged\Markup` interface as the return type

### 8.2 Metamorph

Chirp provides a Metamorph handler (`DecodeLabs\Metamorph\Handler\Tweet`) that allows the parser to be used within Metamorph content transformation pipelines. This integration is optional and only available when Metamorph is installed.

---

## 9. Usage Examples

### 9.1 Basic Parsing

```php
use DecodeLabs\Chirp\Parser;

$parser = new Parser();
$tweet = "Check out this link: https://example.com #awesome @username";
$markup = $parser->parse($tweet);

// $markup is a Markup object that can be rendered
echo $markup;
```

### 9.2 With Metamorph

```php
use DecodeLabs\Metamorph;

$tweet = "Check out this link: https://example.com #awesome @username";
$markup = Metamorph::tweet($tweet);

echo $markup;
```

### 9.3 Handling Empty Input

```php
use DecodeLabs\Chirp\Parser;

$parser = new Parser();
$markup = $parser->parse(null); // Returns null
$markup = $parser->parse('');   // Returns null

if ($markup !== null) {
    echo $markup;
}
```

---

## 10. Implementation Notes (for Contributors)

### 10.1 Regex Patterns

The parser uses complex regex patterns for:
- URL detection (with protocol and domain validation)
- Hashtag detection (supporting both ASCII and full-width characters)
- Username/mention detection (including list mentions)

These patterns are defined as class constants and use Unicode-aware matching where appropriate.

### 10.2 Processing Order

The order of processing is important:
1. URLs are processed first to avoid conflicts with hashtags and mentions
2. Hashtags are processed second
3. Usernames are processed last

This order ensures that URLs containing `#` or `@` characters are not incorrectly parsed as hashtags or mentions.

### 10.3 HTML Escaping

All input text is HTML-escaped before processing to prevent XSS vulnerabilities. The escaping uses PHP's `htmlspecialchars()` with `ENT_QUOTES` flag and UTF-8 encoding.

### 10.4 Link Generation

Links are created using Tagged's `Element::create()` method, which ensures proper HTML generation and attribute handling. All links include:
- `href` attribute with the target URL
- `rel="external nofollow"` attribute
- `target="_blank"` attribute
- CSS class for styling (`url`, `hashtag`, `user`, or `list`)

---

## 11. Testing & Quality

- **Code Quality Score:** 2/5
- **README Quality Score:** 2/5
- **Documentation Score:** 0/5 (this spec)
- **Test Coverage Score:** 0/5

See `composer.json` for supported PHP versions.

---

## 12. Roadmap & Future Ideas

- Make base URL configurable (currently hardcoded to `https://x.com/`)
- Add support for custom CSS classes
- Consider making processing order configurable
- Add support for Twitter/X media entities
- Consider adding tweet metadata parsing (if JSON input is provided)
- Improve test coverage

---

## 13. References

- [Tagged Package](https://github.com/decodelabs/tagged) — HTML markup generation
- [Metamorph Package](https://github.com/decodelabs/metamorph) — Content transformation framework
- [Chorus Package Index](../../../chorus/config/packages.json) — Ecosystem metadata

