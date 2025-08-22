import { event, stripe } from '#src/utils';

export const stripePortal = async () => {
    const session = await stripe.billingPortal.sessions.create({
        customer: event.claims.profile,
        return_url: `https://${process.env.URL}`,
    });

    return {
        url: session.url,
    };
}