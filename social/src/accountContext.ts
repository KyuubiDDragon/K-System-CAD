// Only non-secret selectors live in sessionStorage; credentials remain in HttpOnly cookies.
export function accountHeaders(): Record<string,string> {
  return {'X-Social-Account':sessionStorage.getItem('social-account')||'0','X-Social-Acting':sessionStorage.getItem('social-acting')||'0'};
}
export function selectAccount(id:number,acting=0) {
  sessionStorage.setItem('social-account',String(id));
  sessionStorage.setItem('social-acting',String(acting));
}
export function mediaContext() {
  const h=accountHeaders();return `&account=${encodeURIComponent(h['X-Social-Account'])}&acting=${encodeURIComponent(h['X-Social-Acting'])}`;
}
