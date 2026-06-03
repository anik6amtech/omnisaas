# Knowledge

> Bounded context: document ingestion, chunking, embeddings, the pgvector store (RAG).

- **Plane:** tenant (indexing runs in the `indexing` queue)
- **Epic:** E4 / E6

## Responsibilities
- Ingest FAQ, policies, and (optionally) past conversations; chunk and embed via Prism → OpenRouter embeddings.
- `kb_chunks` table: `embedding vector(1536)` + **HNSW** index (`vector_cosine_ops`), always tenant-scoped (`workspace_id`).
- `IndexKnowledge` job: batch-embed chunks; re-index on FAQ/policy edits and affected product descriptions.
- Tenant-scoped retrieval: `ORDER BY embedding <=> :query_vector LIMIT k`.

## Hard rules
- Embedding **dimension is locked at 1536** to the `kb_chunks.embedding` column — changing the model later means re-embedding the whole corpus (a one-way door).
- **Prices/stock are never vectorized** — they're read live from `products` so the AI can't quote a stale/hallucinated number.

## Status
Skeleton only. pgvector is enabled (extension migration); `kb_chunks`, the indexing job, and retrieval land in E4.
