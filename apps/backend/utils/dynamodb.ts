import { DynamoDB } from "@aws-sdk/client-dynamodb";
import { unmarshall } from '@aws-sdk/util-dynamodb';
const dynamodbClient = new DynamoDB();

export const dynamodb = {
    query: async ({ text: Statement, values: Parameters = null }) => {
        const response = [];
        const getItems = async (nextToken = null) => {
            const { Items, NextToken } = await dynamodbClient.executeStatement({ Statement, Parameters, NextToken: nextToken });
            response.push(...Items.map(obj => unmarshall(obj)));
            return NextToken;
        };
        const nextToken = await getItems();
        if (nextToken) {
            await getItems(nextToken);
        }
        return response;
    },
    insert: async ({ ...data }) => {
        await dynamodbClient.executeStatement({
            Statement: `INSERT INTO "${process.env.DDB}" VALUE {${Object.keys(data).map(key => `'${key}':?`).join(',')}}`,
            Parameters: Object.values(data).map(value => ({ S: value })),
        });
    },
    update: async ({ pk, sk, ...data }) => {
        await dynamodbClient.executeStatement({
            Statement: `UPDATE "${process.env.DDB}" SET ${Object.keys(data).map(key => `"${key}"=?`).join(',')} WHERE "pk"=? AND "sk"=?`,
            Parameters: [
                ...Object.values(data).map(value => ({ S: value })),
                { S: pk },
                { S: sk },
            ]
        });
    },
    delete: async ({ pk, sk }) => {
        await dynamodbClient.executeStatement({
            Statement: `DELETE FROM "${process.env.DDB}" WHERE "pk"=? AND "sk"=?`,
            Parameters: [{ S: pk }, { S: sk }],
        });
    },
}