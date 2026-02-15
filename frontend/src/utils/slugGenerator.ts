/**
 * Utility function to generate URL-friendly slugs from titles
 * Handles German umlauts and special characters
 *
 * @param text - The text to convert to a slug
 * @returns URL-friendly slug string
 *
 * @example
 * generateSlug('Über uns') // returns 'ueber-uns'
 * generateSlug('Häuser & Gebäude!') // returns 'haeuser-gebaeude'
 */
export function generateSlug(text: string): string {
  if (!text) return '';

  return text
    // Convert to lowercase
    .toLowerCase()
    // Replace German umlauts
    .replace(/ä/g, 'ae')
    .replace(/ö/g, 'oe')
    .replace(/ü/g, 'ue')
    .replace(/ß/g, 'ss')
    // Remove accents from other characters
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    // Replace spaces and underscores with hyphens
    .replace(/[\s_]+/g, '-')
    // Remove all non-alphanumeric characters except hyphens
    .replace(/[^a-z0-9-]/g, '')
    // Replace multiple consecutive hyphens with a single hyphen
    .replace(/-+/g, '-')
    // Remove leading and trailing hyphens
    .replace(/^-+|-+$/g, '');
}

/**
 * Generate a unique slug by appending a number if the slug already exists
 *
 * @param text - The text to convert to a slug
 * @param existingSlugs - Array of existing slugs to check against
 * @returns Unique URL-friendly slug string
 *
 * @example
 * generateUniqueSlug('About', ['about']) // returns 'about-2'
 * generateUniqueSlug('About', ['about', 'about-2']) // returns 'about-3'
 */
export function generateUniqueSlug(text: string, existingSlugs: string[]): string {
  let slug = generateSlug(text);
  let counter = 2;
  const baseSlug = slug;

  while (existingSlugs.includes(slug)) {
    slug = `${baseSlug}-${counter}`;
    counter++;
  }

  return slug;
}

export default generateSlug;
