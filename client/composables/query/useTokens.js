import { useQueryClient, useQuery, useMutation } from '@tanstack/vue-query'
import { tokensApi } from '~/api/tokens'

export function useTokens() {
  const queryClient = useQueryClient()
  const { t } = useI18n()

  // Abilities configuration (moved from store)
  const abilities = [
    {
      title: t('runtime.tokens.abilities.manage_integrations'),
      name: 'manage-integrations',
    },
    {
      title: t('runtime.tokens.abilities.forms_read'),
      name: 'forms-read',
    },
    {
      title: t('runtime.tokens.abilities.forms_write'),
      name: 'forms-write',
    },
    {
      title: t('runtime.tokens.abilities.workspaces_read'),
      name: 'workspaces-read',
    },
    {
      title: t('runtime.tokens.abilities.workspaces_write'),
      name: 'workspaces-write',
    },
    {
      title: t('runtime.tokens.abilities.workspace_users_read'),
      name: 'workspace-users-read',
    },
    {
      title: t('runtime.tokens.abilities.workspace_users_write'),
      name: 'workspace-users-write',
    },
  ]

  const getAbility = (name) => {
    return abilities.find((ability) => ability.name === name) ?? {
      name,
      title: name,
    }
  }

  // Queries
  const list = (options = {}) => {
    return useQuery({
      queryKey: ['tokens', 'list'],
      queryFn: () => tokensApi.list(options),
      onSuccess: (data) => {
        data?.forEach(token => {
          queryClient.setQueryData(['tokens', token.id], token)
        })
      },
      ...options
    })
  }

  const detail = (tokenId, options = {}) => {
    return useQuery({
      queryKey: ['tokens', tokenId],
      queryFn: () => {
        // Since there's no individual get endpoint, we get from the cached list
        const cachedTokens = queryClient.getQueryData(['tokens', 'list'])
        return cachedTokens?.find(t => t.id === tokenId) || null
      },
      enabled: !!tokenId,
      ...options
    })
  }

  // Mutations
  const create = (options = {}) => {
    return useMutation({
      mutationFn: (data) => tokensApi.create(data),
      onSuccess: (newToken) => {
      // Built-in cache management
      queryClient.setQueryData(['tokens', newToken.id], newToken)
      
      // Add to list query data
      const currentList = queryClient.getQueryData(['tokens', 'list'])
      if (currentList) {
        queryClient.setQueryData(['tokens', 'list'], [newToken, ...currentList])
      }
      useAlert().success(t('runtime.tokens.created'))
      },
      ...options
    })
  }

  const remove = (options = {}) => {
    return useMutation({
      mutationFn: (tokenId) => tokensApi.delete(tokenId),
      onSuccess: (data, deletedTokenId) => {
      // Built-in cache management
      queryClient.removeQueries({ queryKey: ['tokens', deletedTokenId] })
      
      // Remove from list query data if loaded
      const currentList = queryClient.getQueryData(['tokens', 'list'])
      if (currentList) {
        queryClient.setQueryData(
          ['tokens', 'list'],
          currentList.filter(token => token.id != deletedTokenId) // Use != for loose equality
        )
      }
      useAlert().success(t('runtime.tokens.deleted'))
      },
      ...options
    })
  }

  const invalidateAll = () => {
    queryClient.invalidateQueries({ queryKey: ['tokens'] })
  }

  const getTokenById = (tokenId) => {
    return queryClient.getQueryData(['tokens', tokenId])
  }

  const getTokenByName = (name) => {
    const tokens = queryClient.getQueryData(['tokens', 'list'])
    if (Array.isArray(tokens)) {
      return tokens.find(t => t.name === name) || null
    }
    if (tokens?.data) {
      return tokens.data.find(t => t.name === name) || null
    }
    return null
  }

  return {
    // Queries
    list,
    detail,
    
    // Mutations
    create,
    remove,
    
    // Utilities
    invalidateAll,
    getTokenById,
    getTokenByName,
    
    // Abilities (moved from store)
    abilities,
    getAbility
  }
} 