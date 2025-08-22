import { graphqlQuery } from '#src/utils';
import { EC2Client, DescribeSecurityGroupsCommand, RevokeSecurityGroupIngressCommand, AuthorizeSecurityGroupIngressCommand } from "@aws-sdk/client-ec2";
const ec2Client = new EC2Client();

export const runEveryMinute = async () => {
    // Sync IP Ranges
    const newIps = (await (await fetch(`https://ip-ranges.amazonaws.com/ip-ranges.json`)).json()).prefixes
        .filter(({ region, service }) => region === 'us-east-2' && service === 'EC2').map(obj => obj.ip_prefix);
    const existingIps = (await ec2Client.send(new DescribeSecurityGroupsCommand({ GroupIds: [process.env.DB_SECURITY_GROUP] }))).SecurityGroups
        .map(securityGroup => securityGroup.IpPermissions
            .map(ipPermission => ipPermission.IpRanges
                .filter(obj => obj.Description === 'Lambda')
                .map(ipRange => ipRange.CidrIp)
            ).flat()
        ).flat();
    const addedIps = newIps.filter(newIp => !existingIps.includes(newIp));
    const removedIps = existingIps.filter(existingIp => !newIps.includes(existingIp));
    if (removedIps.length > 0) {
        await ec2Client.send(new RevokeSecurityGroupIngressCommand({
            GroupId: process.env.DB_SECURITY_GROUP,
            IpPermissions: removedIps.map(ip => ({
                FromPort: 5432,
                IpProtocol: 'tcp',
                IpRanges: [{
                    CidrIp: ip,
                    Description: 'Lambda'
                }],
                ToPort: 5432,
            }))
        }));
    }
    if (addedIps.length > 0) {
        await ec2Client.send(new AuthorizeSecurityGroupIngressCommand({
            GroupId: process.env.DB_SECURITY_GROUP,
            IpPermissions: addedIps.map(ip => ({
                FromPort: 5432,
                IpProtocol: 'tcp',
                IpRanges: [{
                    CidrIp: ip,
                    Description: 'Lambda'
                }],
                ToPort: 5432,
            }))
        }));
    }

    // Perform health check
    const response = await graphqlQuery({ query: `{users(limit:1){id}}` });
    if (!response?.users?.[0]?.id) {
        await fetch(process.env.SLACK_WEBHOOK, {
            method: 'POST',
            body: JSON.stringify({
                text: `*equalifyv2* - Database connection failure detected`
            })
        })
    }
    return;
}