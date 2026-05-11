import { db, event } from '#src/utils';

export const flagBlockerSummary = async () => {
    const { summary_id } = event.body as { summary_id: string };

    await db.connect();
    await db.query({
        text: `UPDATE "blocker_llm_summaries" SET "flagged" = true WHERE "id" = $1`,
        values: [summary_id],
    });
    await db.clean();

    return { status: 'success' };
};
