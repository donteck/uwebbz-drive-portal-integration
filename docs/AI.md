# AI Setup and Usage

## Supported providers

UWEBBZ LMS 5 currently includes provider settings for:

- OpenAI
- Google Gemini
- Anthropic Claude

Configure providers under **UWEBBZ LMS → AI Providers**.

## API key handling

API keys are entered once in the administrator settings. The normal teacher-facing AI Lesson Builder does not need to display the key.

Never place API keys in source files, JavaScript, screenshots, support tickets, or GitHub commits.

## AI Lesson Builder

Use AI Lesson Builder to transform teacher-provided content into a structured WordPress lesson.

Typical workflow:

1. Paste source content.
2. Enter an optional preferred title.
3. Choose Beginner, Intermediate, or Advanced.
4. Choose lesson depth.
5. Choose the destination course.
6. Decide whether to include a knowledge check.
7. Choose Draft or Publish.
8. Optionally enable Drive sync where supported.
9. Click **Generate Lesson with AI**.

The lesson generator is instructed to preserve the meaning of the provided source and avoid inventing unsupported facts. Teachers should still review generated educational content before publishing.

## AI Course Architect

AI Course Architect creates a reviewable course blueprint from source material.

Current structure:

`Course → Modules → Lessons`

The AI blueprint is stored temporarily for review. The teacher explicitly clicks **Build Course Structure** before WordPress posts are created.

## Recommended use

AI should assist curriculum design, not silently replace instructor review. Verify technical accuracy, sequencing, examples, assessment quality, and suitability for the intended learners before publishing.
