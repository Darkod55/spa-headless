import type { APIRoute } from "astro";

export const prerender = false;

interface ContactBody {
  name?: unknown;
  email?: unknown;
  organisation?: unknown;
  message?: unknown;
  company_website?: unknown;
  turnstileToken?: unknown;
}

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function text(value: unknown, max: number): string {
  if (typeof value !== "string") {
    return "";
  }
  return value.trim().slice(0, max);
}

async function turnstilePasses(token: string, remoteIp: string | undefined): Promise<boolean> {
  const secret = import.meta.env.TURNSTILE_SECRET_KEY?.trim();
  if (!secret) {
    return true;
  }
  if (!token) {
    return false;
  }

  const body = new URLSearchParams({ secret, response: token });
  if (remoteIp) {
    body.set("remoteip", remoteIp);
  }

  const response = await fetch("https://challenges.cloudflare.com/turnstile/v0/siteverify", {
    method: "POST",
    body,
  });
  if (!response.ok) {
    return false;
  }
  const payload = (await response.json()) as { success?: boolean };
  return payload.success === true;
}

function json(body: unknown, status: number): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

export const POST: APIRoute = async ({ request, clientAddress }) => {
  let incoming: ContactBody;
  try {
    incoming = (await request.json()) as ContactBody;
  } catch {
    return json({ ok: false, error: "Send the form as JSON." }, 400);
  }

  if (text(incoming.company_website, 200) !== "") {
    return json({ ok: true }, 200);
  }

  const name = text(incoming.name, 200);
  const email = text(incoming.email, 200);
  const message = text(incoming.message, 5000);
  text(incoming.organisation, 200);

  if (!name || !email || !EMAIL.test(email) || !message) {
    return json({ ok: false, error: "Name, a valid email, and a message are required." }, 400);
  }

  const allowed = await turnstilePasses(text(incoming.turnstileToken, 2048), clientAddress);
  if (!allowed) {
    return json({ ok: false, error: "The spam check failed. Try again." }, 403);
  }

  return json(
    {
      ok: false,
      error:
        "The message passed the scaffold checks, but mail delivery is not configured yet. Use Fluent Forms, or wire a transport, when you are ready.",
    },
    501,
  );
};
