# AI for Artisans — Mastering the Laravel AI SDK

### A Complete Guide to Building AI-Powered Applications with Laravel

**By Fakhar Zaman Khan**

---

## Table of Contents

### Front Matter
- [Disclaimer](./01-PREFACE.md)
- [About the Author](./01-PREFACE.md#about-the-author)
- [Preface](./01-PREFACE.md#preface)
- [Who This Book Is For](./01-PREFACE.md#who-this-book-is-for)
- [How to Read This Book](./01-PREFACE.md#how-to-read-this-book)
- [Conventions Used](./01-PREFACE.md#conventions-used)

---

### Part I — The Foundation

- **[Chapter 1: The AI Revolution Meets PHP](./02-PART-I-FOUNDATION.md#chapter-1-the-ai-revolution-meets-php)**
  - 1.1 Why AI Matters for Laravel Developers
  - 1.2 The State of AI in 2026
  - 1.3 Laravel's AI Vision: SDK, MCP, and Boost
  - 1.4 What You'll Build in This Book

- **[Chapter 2: Setting Up Your AI Development Environment](./02-PART-I-FOUNDATION.md#chapter-2-setting-up-your-ai-development-environment)**
  - 2.1 Prerequisites: Laravel 12 and PHP 8.4
  - 2.2 Installing the Laravel AI SDK
  - 2.3 Configuring AI Providers
  - 2.4 Your First AI Interaction
  - 2.5 Understanding the Provider Ecosystem

---

### Part II — Agents: The Heart of the SDK

- **[Chapter 3: Understanding Agents](./03-PART-II-AGENTS.md#chapter-3-understanding-agents)**
  - 3.1 What Are AI Agents?
  - 3.2 The Agent Architecture in Laravel
  - 3.3 Creating Your First Agent
  - 3.4 Prompting Agents
  - 3.5 Agent Contracts and Interfaces
  - 3.6 Anonymous Agents for Quick Prototyping

- **[Chapter 4: Conversations and Memory](./03-PART-II-AGENTS.md#chapter-4-conversations-and-memory)**
  - 4.1 Stateless vs. Conversational Agents
  - 4.2 The Conversational Interface
  - 4.3 RemembersConversations: Automatic Memory
  - 4.4 Starting and Continuing Conversations
  - 4.5 Designing Conversation Flows

- **[Chapter 5: Structured Output](./03-PART-II-AGENTS.md#chapter-5-structured-output)**
  - 5.1 Why Structured Output Matters
  - 5.2 Defining JSON Schemas
  - 5.3 Accessing Structured Responses
  - 5.4 Real-World Schema Patterns

- **[Chapter 6: Tools — Extending Agent Capabilities](./03-PART-II-AGENTS.md#chapter-6-tools--extending-agent-capabilities)**
  - 6.1 Understanding AI Tools
  - 6.2 Creating Custom Tools
  - 6.3 Tool Schemas and Validation
  - 6.4 Provider Tools: WebSearch, WebFetch, FileSearch
  - 6.5 The Similarity Search Tool for RAG
  - 6.6 Combining Multiple Tools

- **[Chapter 7: Agent Configuration and Middleware](./03-PART-II-AGENTS.md#chapter-7-agent-configuration-and-middleware)**
  - 7.1 PHP Attributes for Agent Configuration
  - 7.2 Provider, Model, Temperature, and Timeout
  - 7.3 UseCheapestModel vs. UseSmartestModel
  - 7.4 Building Agent Middleware
  - 7.5 Logging, Rate Limiting, and Guardrails

---

### Part III — Multimodal AI

- **[Chapter 8: Image Generation](./04-PART-III-MULTIMODAL.md#chapter-8-image-generation)**
  - 8.1 Generating Images with Laravel
  - 8.2 Aspect Ratios, Quality, and Timeouts
  - 8.3 Image Remixing with Attachments
  - 8.4 Storing Generated Images
  - 8.5 Queued Image Generation

- **[Chapter 9: Audio — Text-to-Speech and Transcription](./04-PART-III-MULTIMODAL.md#chapter-9-audio--text-to-speech-and-transcription)**
  - 9.1 Generating Speech from Text (TTS)
  - 9.2 Voices, Gender, and Instructions
  - 9.3 Transcribing Audio to Text (STT)
  - 9.4 Speaker Diarization
  - 9.5 Queued Audio Processing

- **[Chapter 10: Attachments and File Handling](./04-PART-III-MULTIMODAL.md#chapter-10-attachments-and-file-handling)**
  - 10.1 Attaching Documents and Images to Prompts
  - 10.2 Storing Files with AI Providers
  - 10.3 Referencing Stored Files
  - 10.4 File Lifecycle Management

---

### Part IV — Embeddings, Search, and RAG

- **[Chapter 11: Vector Embeddings](./05-PART-IV-EMBEDDINGS-RAG.md#chapter-11-vector-embeddings)**
  - 11.1 What Are Embeddings?
  - 11.2 Generating Embeddings in Laravel
  - 11.3 PostgreSQL and pgvector Setup
  - 11.4 Storing Embeddings in Your Database
  - 11.5 Caching Embeddings for Performance

- **[Chapter 12: Semantic Search and Similarity](./05-PART-IV-EMBEDDINGS-RAG.md#chapter-12-semantic-search-and-similarity)**
  - 12.1 Beyond Keyword Search
  - 12.2 The whereVectorSimilarTo Query
  - 12.3 Distance Methods and Indexing
  - 12.4 Building a Semantic Search Feature

- **[Chapter 13: Retrieval-Augmented Generation (RAG)](./05-PART-IV-EMBEDDINGS-RAG.md#chapter-13-retrieval-augmented-generation-rag)**
  - 13.1 Understanding RAG Architecture
  - 13.2 Vector Stores: Creating and Managing
  - 13.3 Adding Files to Vector Stores
  - 13.4 The FileSearch Provider Tool
  - 13.5 Building a Knowledge Base Agent

- **[Chapter 14: Document Reranking](./05-PART-IV-EMBEDDINGS-RAG.md#chapter-14-document-reranking)**
  - 14.1 Why Reranking Improves Results
  - 14.2 Reranking Documents and Collections
  - 14.3 Combining Search with Reranking

---

### Part V — Real-Time AI and Production Patterns

- **[Chapter 15: Streaming Responses](./06-PART-V-REALTIME-PRODUCTION.md#chapter-15-streaming-responses)**
  - 15.1 Why Streaming Matters for UX
  - 15.2 Server-Sent Events (SSE) Streaming
  - 15.3 The Vercel AI SDK Protocol
  - 15.4 Manual Event Iteration
  - 15.5 Building a Chat Interface

- **[Chapter 16: Broadcasting and Queuing](./06-PART-V-REALTIME-PRODUCTION.md#chapter-16-broadcasting-and-queuing)**
  - 16.1 Broadcasting Streamed Events
  - 16.2 Queuing Agent Prompts
  - 16.3 Background AI Processing
  - 16.4 Error Handling with then() and catch()

- **[Chapter 17: Failover and Resilience](./06-PART-V-REALTIME-PRODUCTION.md#chapter-17-failover-and-resilience)**
  - 17.1 Multi-Provider Failover
  - 17.2 Rate Limit Handling
  - 17.3 Graceful Degradation Patterns
  - 17.4 Monitoring AI Usage with Events

- **[Chapter 18: Testing AI Features](./06-PART-V-REALTIME-PRODUCTION.md#chapter-18-testing-ai-features)**
  - 18.1 Why AI Testing Is Different
  - 18.2 Faking Agents, Images, Audio, and More
  - 18.3 Assertions and Expectations
  - 18.4 Preventing Stray API Calls
  - 18.5 Testing Queued Operations
  - 18.6 A Complete Test Suite Example

---

### Part VI — The MCP Ecosystem

- **[Chapter 19: Laravel MCP — Model Context Protocol](./07-PART-VI-MCP-ECOSYSTEM.md#chapter-19-laravel-mcp--model-context-protocol)**
  - 19.1 What Is the Model Context Protocol?
  - 19.2 Installing and Configuring Laravel MCP
  - 19.3 Creating MCP Servers
  - 19.4 Building MCP Tools
  - 19.5 Resources and Prompts
  - 19.6 Authentication with OAuth and Sanctum
  - 19.7 Testing MCP Servers

---

### Part VII — Real-World Projects

- **[Chapter 20: Project — AI-Powered Customer Support Bot](./08-PART-VII-PROJECTS.md#chapter-20-project--ai-powered-customer-support-bot)**
  - 20.1 Project Architecture
  - 20.2 Building the Support Agent
  - 20.3 Knowledge Base with RAG
  - 20.4 Streaming Chat Interface
  - 20.5 Conversation History
  - 20.6 Testing the Complete System

- **[Chapter 21: Project — E-Commerce Product Description Generator](./08-PART-VII-PROJECTS.md#chapter-21-project--e-commerce-product-description-generator)**
  - 21.1 The Business Problem
  - 21.2 Building the Description Writer Agent
  - 21.3 Structured Output for SEO
  - 21.4 Bulk Generation with Queues
  - 21.5 Deduplication with Embeddings

- **[Chapter 22: Project — Multi-Modal Content Platform](./08-PART-VII-PROJECTS.md#chapter-22-project--multi-modal-content-platform)**
  - 22.1 Architecture Overview
  - 22.2 Blog Post Generator with AI
  - 22.3 Auto-Generated Featured Images
  - 22.4 Audio Narration for Articles
  - 22.5 Semantic Content Search

---

### Appendices

- **[Appendix A: Provider Reference](./09-APPENDICES.md#appendix-a-provider-reference)**
- **[Appendix B: Complete API Reference](./09-APPENDICES.md#appendix-b-complete-api-reference)**
- **[Appendix C: Event Reference](./09-APPENDICES.md#appendix-c-event-reference)**
- **[Appendix D: Troubleshooting Guide](./09-APPENDICES.md#appendix-d-troubleshooting-guide)**
- **[Appendix E: Resources and Further Reading](./09-APPENDICES.md#appendix-e-resources-and-further-reading)**

---

**Edition**: First Edition, 2026
**Framework**: Laravel 12.x
**SDK Version**: laravel/ai v0.2.x
**PHP Version**: 8.4+
