import { db, hashStringToUuid } from "#src/utils"

export const manuallyAddTags = async () => {
    await db.connect();
    const tags = ['ACT', 'best-practice', 'cat.aria', 'cat.color', 'cat.keyboard', 'cat.parsing', 'cat.semantics', 'EN-301-549', 'EN-9.1.4.1', 'EN-9.1.4.3', 'EN-9.2.4.1', 'EN-9.4.1.2', 'section508', 'section508.22.o', 'TT13.a', 'TT13.c', 'TT9.a', 'TTv5', 'wcag141', 'wcag143', 'wcag2a', 'wcag2aa', 'wcag412'];
    for (const tag of tags) {
        const hash = hashStringToUuid(tag);
        await db.query({
            text: `INSERT INTO "tags" ("id", "content") VALUES ($1, $2)`,
            values: [hash, tag],
        })
    }
    await db.clean();
}