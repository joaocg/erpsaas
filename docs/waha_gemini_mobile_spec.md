# Plano funcional e arquitetura – Integração WhatsApp + Gemini

## Objetivo
Entregar um protótipo funcional (mobile-first) para cadastro e automação de finanças, consultas e exames, unindo Laravel + Filament, WAHA para WhatsApp e Google Gemini para leitura de anexos. O sistema continua contábil (dupla entrada, competência), mas acrescenta fluxo inteligente via WhatsApp e processamento automático de mídias.

## Arquitetura de alto nível
- **Frontend mobile-first (SPA/Vite + Filament)**: telas leves para cadastro/login (WhatsApp, e-mail, senha), lançamentos, listagens e envio de anexos.
- **Backend Laravel/Filament**: autenticação, modelos contábeis, filas, webhooks do WAHA, orquestração de chamadas Gemini e regras de categorização.
- **Broker/Filas (Redis)**: desacoplar recebimento de mensagens WAHA, processamento Gemini e criação de registros.
- **WAHA (WhatsApp API)**: recebe mensagens/mídias e envia respostas guiadas; webhook aponta para `/api/webhooks/waha`.
- **Google Gemini**: leitura de imagens/PDFs; retorna resumo, tópicos, valores e categorias sugeridas.
- **Storage (S3 ou local)**: guarda anexos e metadados processados.
- **Banco**: tabelas contábeis (lançamentos de competência e dupla entrada) + saúde (consultas/exames) + categorização personalizada.

### Módulos principais
1. **Autenticação omnicanal**: cadastro via WhatsApp, e-mail e senha. Confirmação de número via código enviado no WAHA.
2. **Finanças**: despesas/receitas com lançamento de dupla entrada, categorias personalizáveis e anexos.
3. **Saúde**: consultas e exames com datas, status, profissionais, laudos e anexos.
4. **Processamento IA**: rotinas que recebem anexos, enviam ao Gemini, extraem dados e alimentam o lançamento correto.
5. **Orquestração WhatsApp**: entendimento de mensagens naturais para decidir ação (registrar, listar, buscar, enviar anexo, onboarding).

## Modelo de dados sugerido
Campos mínimos (adicionar timestamps e soft deletes conforme padrão Laravel).

- **users**: id, name, email, phone_e164 (único), whatsapp_opt_in_at, password, two_factor_secret.
- **categories**: id, user_id (nullable para padrão), name, type (expense|income|exam|appointment), icon, color.
- **attachments**: id, user_id, path, mime, size, source (whatsapp|web), gemini_status, gemini_summary, gemini_topics (json), gemini_amount, gemini_currency, gemini_detected_type (expense|income|exam|appointment), raw_payload (json).
- **ledgers** (lançamentos contábeis): id, user_id, category_id, direction (debit|credit), amount, currency, occurred_on, description, attachment_id.
- **transactions**: id, user_id, type (expense|income), total_amount, currency, occurred_on, description, category_id, attachment_id.
- **medical_appointments**: id, user_id, provider_name, specialty, occurred_on, status (scheduled|done|canceled), category_id, attachment_id, notes.
- **medical_exams**: id, user_id, exam_type, lab_name, occurred_on, status, category_id, attachment_id, notes, results_json.
- **whatsapp_sessions**: id, user_id, phone_e164, last_intent, state_payload (json), last_message_at.
- **webhook_logs**: id, provider (waha|gemini), payload (json), status_code, processed_at, error.

### Regras de contabilidade
- Cada **transaction** gera lançamentos em **ledgers**: 
  - `expense`: débito em despesas/categoria, crédito em caixa/banco.
  - `income`: débito em caixa/banco, crédito em receitas/categoria.
- Datas usam **regime de competência** (`occurred_on`), e relatórios usam esse campo.

## Rotas API necessárias (exemplos)
- **Autenticação**: `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/whatsapp/verify`, `POST /api/auth/whatsapp/confirm`.
- **WhatsApp webhook**: `POST /api/webhooks/waha` (assinatura e verificação do provider).
- **Anexos**: `POST /api/attachments` (upload web), `POST /api/attachments/:id/process` (força reprocessamento Gemini).
- **Finanças**: `GET/POST /api/transactions`, `GET /api/transactions/recent`, `GET /api/ledgers/report`.
- **Consultas/Exames**: `GET/POST /api/appointments`, `GET/POST /api/exams`, `GET /api/exams/by-date`.
- **Categorias**: `GET/POST /api/categories`.

## Fluxo WhatsApp + WAHA
1. WAHA recebe mensagem/mídia e dispara webhook para Laravel com número e payload.
2. Backend busca `whatsapp_sessions` pelo número:
   - **Usuário existe** → enfileira mensagem para interpretação (NLP simples + intents) e responde menu contextual.
   - **Usuário não existe** → envia link de cadastro mobile-first e opção de pré-cadastro via WhatsApp (coleta nome/email).
3. Se mensagem contém mídia: salva em `attachments` com `source=whatsapp`, fila tarefa `ProcessGeminiJob`.
4. Se mensagem é texto: classifica intent (registrar despesa/receita/consulta/exame, listar, ajuda). Usa regras + LLM opcional.
5. Job de intent executa a ação solicitada (criar lançamento, listar últimos, agendar consulta, etc.) e responde pelo WAHA.

### Exemplos de intents no WhatsApp
- "Registrar despesa de R$ 120 no mercado" → cria transaction expense, gera lançamentos e confirma.
- "Mostrar últimos gastos" → envia resumo dos 5 lançamentos recentes.
- "Enviar imagem" → orienta a anexar; ao receber mídia executa fluxo Gemini.
- "Cadastrar consulta médica" → coleta data/profissional e grava em `medical_appointments`.
- "Listar exames por data" → retorna exames filtrados.

## Fluxo de processamento de anexos (Gemini)
1. Usuário envia foto/PDF via WhatsApp ou web.
2. API salva arquivo em storage e cria `attachments` com status `pending`.
3. Job `ProcessGeminiJob` envia arquivo ao Gemini com prompt especializado (finanças/saúde).
4. Gemini retorna resumo, tópicos, valores, moeda, categoria sugerida e tipo (despesa, receita, consulta, exame).
5. Sistema grava campos `gemini_*` no attachment e decide ação:
   - Se tipo = `expense|income` → cria `transaction` + lançamentos em `ledgers`.
   - Se tipo = `exam|appointment` → cria registro correspondente e vincula attachment.
6. Envia mensagem pelo WAHA com resumo e link para edição no app.

## Telas mobile-first (Filament + Tailwind)
- **Onboarding**: formulário com WhatsApp (validação E.164), e-mail e senha; botão "Receber código pelo WhatsApp".
- **Dashboard rápido**: cartões de saldo, últimos lançamentos e atalhos para registrar despesa/receita/consulta/exame.
- **Registrar lançamento**: form simples com valor, categoria, data, descrição, anexo (camera/gallery) e tipo.
- **Listagem**: feed de lançamentos recentes com filtros por categoria/data e chips para saúde (consultas/exames).
- **Envio de anexos**: upload direto + status de processamento Gemini.
- **Categorias**: CRUD com cores/ícones; sugestões automáticas vindas do Gemini.

## Passo a passo da automação por imagem
1. Receber mídia via WhatsApp → salvar attachment.
2. Enfileirar `ProcessGeminiJob` com caminho do arquivo.
3. Enviar para Gemini e obter JSON com valores/categorias/resumo.
4. Criar ou atualizar registro (transaction/consulta/exame) conforme `gemini_detected_type`.
5. Gerar lançamentos contábeis de competência se for finança.
6. Responder usuário pelo WhatsApp com confirmação, valor, categoria e link de edição.

## Considerações de segurança e operações
- Validar assinatura dos webhooks WAHA; armazenar logs em `webhook_logs`.
- Limitar tamanho/tipo de arquivo; varredura antivírus opcional.
- Usar filas para chamadas ao Gemini e respostas WAHA para evitar timeouts.
- Versionar prompts Gemini e armazenar payloads para auditoria.
- Padrão mobile-first: componentes Filament responsivos e foco em UX de toques únicos.
