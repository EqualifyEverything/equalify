import { useNavigate } from "react-router-dom"
import * as Tabs from "@radix-ui/react-tabs";
import { useUser } from "../queries"
import { InvitesTable, UsersTable, CoBrandingInput } from "../components"
import { SkeletonAccount } from "#src/components/Skeleton.tsx"
import style from "./Account.module.scss";
import { Card } from "#src/components/Card.tsx";
import { StyledButton } from "#src/components/StyledButton.tsx";

export const Account = () => {
    const { data: user, isLoading } = useUser();
    const isAdmin = user?.type === 'admin';
    const navigate = useNavigate();

    return <div className={style.Account}>
        <h1 className="initial-focus-element">Account</h1>
        {isLoading ? (
            <SkeletonAccount />
        ) : user ? (
            <Tabs.Root defaultValue="account" className={style.adminTabs}>
                <Tabs.List aria-label="Account settings" className={style.tabList}>
                    <Tabs.Trigger value="account" className={style.tabTrigger}>Account</Tabs.Trigger>
                    {isAdmin && <Tabs.Trigger value="users" className={style.tabTrigger}>Users</Tabs.Trigger>}
                    {isAdmin && <Tabs.Trigger value="invites" className={style.tabTrigger}>Invites</Tabs.Trigger>}
                    {isAdmin && <Tabs.Trigger value="system" className={style.tabTrigger}>System</Tabs.Trigger>}
                </Tabs.List>
                <Tabs.Content value="account">
                    <div className="cards-50">
                        <Card variant="light">
                                <h2>Account Information</h2>
                                <p><strong>Name:</strong> {user.name}</p>
                                <p><strong>Email:</strong> {user.email}</p>
                                <p><strong>Role:</strong> {user.type ? user.type.charAt(0).toUpperCase() + user.type.slice(1) : 'Member'}</p>
                            <StyledButton label="Logout" variant="light" onClick={() => navigate("/logout")} />
                        </Card>
                    </div>
                </Tabs.Content>
                {isAdmin && (
                    <>
                        <Tabs.Content value="users">
                            <Card variant="light"><UsersTable /></Card>
                        </Tabs.Content>
                        <Tabs.Content value="invites">
                            <Card variant="light"><InvitesTable /></Card>
                        </Tabs.Content>
                        <Tabs.Content value="system">

                            <div className="cards-50">
                                <Card variant="light"><CoBrandingInput /></Card>
                            </div>
                        </Tabs.Content>
                    </>
                )}
            </Tabs.Root>
        ) : null}
    </div>
}