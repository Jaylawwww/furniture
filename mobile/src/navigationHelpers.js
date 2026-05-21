/**
 * Walks up navigators until we find one that hosts the Auth modal (root stack).
 */
export function navigateToAuth(navigation, screen = 'Login') {
  let parent = navigation.getParent();
  while (parent) {
    const state = parent.getState?.();
    if (state?.routeNames?.includes('Auth')) {
      parent.navigate('Auth', { screen });
      return true;
    }
    parent = parent.getParent();
  }
  return false;
}
