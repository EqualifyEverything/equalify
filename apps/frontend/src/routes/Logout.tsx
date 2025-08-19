import { useEffect } from 'react'
import { useGlobalStore } from '../utils'
import { useNavigate } from 'react-router-dom';
import * as Auth from 'aws-amplify/auth'
import { useQueryClient } from '@tanstack/react-query';

export const Logout = () => {
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const { setLoading, setAuthenticated } = useGlobalStore();
    useEffect(() => {
        const async = async () => {
            setLoading(true);
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