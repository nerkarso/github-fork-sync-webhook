import type { VercelRequest, VercelResponse } from "@vercel/node";

// Environment variables
const GITHUB_AUTH_TOKEN = process.env.GITHUB_AUTH_TOKEN as string;
const USER_AGENT = process.env.USER_AGENT as string;

export async function handler(req: VercelRequest, res: VercelResponse) {
  // Only allow POST requests
  if (req.method !== "POST") {
    res.status(405).json({ error: "Method not allowed" });
    return;
  }

  // Get data from the query string
  const owner = req.query.owner as string;
  let repo = req.query.repo as string;
  let branch = "master";

  // If no repo is provided, use from the payload
  if (!repo) {
    repo = req.body?.repository?.name;
  }

  // Get the branch from the payload
  if (req.body?.ref) {
    branch = req.body?.ref?.split("/").pop();
  }

  try {
    const result = await mergeUpstream({ owner, repo, branch });
    res.status(200).json(result);
  } catch (ex) {
    res.status(500).json({ error: ex.message });
  }
}

type TMergeUpstreamOptions = {
  owner: string;
  repo: string;
  branch: string;
};

function mergeUpstream({ owner, repo, branch }: TMergeUpstreamOptions) {
  return fetch(`https://api.github.com/repos/${owner}/${repo}/merge-upstream`, {
    method: "POST",
    headers: {
      Accept: "application/vnd.github.v3+json",
      Authorization: `Token ${GITHUB_AUTH_TOKEN}`,
      "Content-Type": "application/json",
      "User-Agent": USER_AGENT,
    },
    body: JSON.stringify({ branch }),
  }).then((res) => res.json());
}
