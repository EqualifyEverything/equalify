import { useEffect } from 'react'
import { useGlobalStore } from '../utils'
import { useNavigate } from 'react-router-dom';
import * as Auth from 'aws-amplify/auth'
import { useQueryClient } from '@tanstack/react-query';

export const Logout = () => {
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const { setLoading, setAuthenticated, setSsoAuthenticated } = useGlobalStore();
    useEffect(() => {
        const async = async () => {
            setLoading(true);
            // Clear SSO token if exists
            localStorage.removeItem('sso_token');
            setSsoAuthenticated(false);
            await Auth.signOut();
            setAuthenticated(false);
            queryClient.clear();
            setLoading(false);
            navigate('/login');
        }
        async();
    }, [])
    return null;
}