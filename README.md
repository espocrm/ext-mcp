# MCP server for EspoCRM

An official extension.

With the extension installed, EspoCRM can act as an MCP server, allowing AI agents to access CRM data and perform operations.

Important: Only MCP protocol version 2026-07-28 is supported. Make sure your MCP client supports this version.

The MCP extension is:

- **Configurable** – Admins can control which tools are exposed to MCP clients and tailor their behavior to their needs.
- **Extensible** – Developers can add custom tools and extend the MCP server with their own functionality.

## Installation

Download an extension [package](https://github.com/espocrm/ext-mcp/releases)
and [install](https://docs.espocrm.com/administration/extensions/) it in your EspoCRM instance.

## Setting up

An administrator can create multiple MCP endpoints, each will function as a separate MCP server. For an MCP endpoint,
the administrator configures supported features. Each feature corresponds to an MCP tool.

To create an MCP endpoint, follow: Administration > MCP Endpoints.

Each feature type has its own set of parameters. For example, in a Find feature, you can configure what fields are exposed and
what filters are available.

The ability to configure what is exposed helps keep the context window small.
For example, if your MCP server is intended for a customer support team, you can whitelist only a small set of tools
and limit each tool to what is needed.

Currently supported feature types:

- Find – Lists and searches records.
- Read – Reads a record.
- Create – Creates a record.
- Update – Updates a record.
- Delete – Deletes a record.
- Record Stream – Lists and searches in a record's stream.

What is exposed as tools is also controlled by the user's access rights.
For example, if a user does not have permission to create Leads, the client won't see the *Create_Lead* tool.

## Authentication

### API User

To use an [API user](https://docs.espocrm.com/development/api/#setting-up),
you need to configure the MCP client to pass the `X-Api-Key` header.

### OAuth 2.0

As of EspoCRM v10.1, it will be possible to use OAuth 2.0 for authentication.

## Customization

The framework allows developers to define and implement custom features (tools) in a future-proof way. See metadata > app > mcpFeatures.
