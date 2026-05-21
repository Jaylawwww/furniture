import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { colors, spacing } from '../theme';

export function ProductCard({ product, onPress, compact = false }) {
  return (
    <Pressable style={styles.card} onPress={onPress}>
      {product.imageUrl ? (
        <Image source={{ uri: product.imageUrl }} style={[styles.image, compact && styles.imageCompact]} resizeMode="cover" />
      ) : (
        <View style={[styles.image, compact && styles.imageCompact, styles.placeholder]}>
          <Text style={styles.placeholderText}>No image</Text>
        </View>
      )}
      <View style={styles.body}>
        <Text style={styles.name} numberOfLines={2}>{product.name}</Text>
        {product.category?.name ? (
          <Text style={styles.category}>{product.category.name}</Text>
        ) : null}
        <Text style={styles.price}>₱{Number(product.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</Text>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.card,
    borderRadius: 12,
    overflow: 'hidden',
    marginBottom: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  image: { width: '100%', height: 180, backgroundColor: '#f0f0f0' },
  imageCompact: { height: 140 },
  placeholder: { alignItems: 'center', justifyContent: 'center' },
  placeholderText: { color: colors.textMuted },
  body: { padding: spacing.md },
  name: { fontSize: 17, fontWeight: '600', color: colors.text, marginBottom: 4 },
  category: { fontSize: 13, color: colors.accent, marginBottom: 6 },
  price: { fontSize: 16, fontWeight: '700', color: colors.text },
});
