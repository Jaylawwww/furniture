import React, { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Image, ScrollView, StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import { fetchProduct } from '../api';
import { colors, spacing } from '../theme';

export default function ProductDetailScreen({ route }) {
  const { width } = useWindowDimensions();
  const { id } = route.params;
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);

  const imageHeight = useMemo(() => {
    const h = Math.round(width * 0.55);
    return Math.min(380, Math.max(220, h));
  }, [width]);

  const contentMaxWidth = useMemo(() => Math.min(560, width - spacing.lg * 2), [width]);

  useEffect(() => {
    fetchProduct(id)
      .then(setProduct)
      .catch(() => setProduct(null))
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (!product) {
    return (
      <View style={styles.centered}>
        <Text style={styles.muted}>Product not found.</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.scrollOuter}>
      <View style={[styles.sheet, { maxWidth: contentMaxWidth, alignSelf: 'center', width: '100%' }]}>
        {product.imageUrl ? (
          <Image source={{ uri: product.imageUrl }} style={[styles.image, { height: imageHeight }]} resizeMode="cover" />
        ) : (
          <View style={[styles.image, styles.placeholder, { height: imageHeight }]}>
            <Text style={styles.muted}>No image</Text>
          </View>
        )}
        <View style={styles.body}>
          <Text style={styles.name}>{product.name}</Text>
          {product.category?.name ? (
            <Text style={styles.category}>{product.category.name}</Text>
          ) : null}
          <Text style={styles.price}>
            ₱{Number(product.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}
          </Text>
          {product.stock != null ? (
            <Text style={styles.stock}>{product.stock > 0 ? `${product.stock} in stock` : 'Out of stock'}</Text>
          ) : null}
          {product.description ? (
            <Text style={styles.description}>{product.description}</Text>
          ) : null}
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scrollOuter: { paddingBottom: spacing.xl, flexGrow: 1 },
  sheet: { backgroundColor: colors.card, borderBottomWidth: StyleSheet.hairlineWidth, borderColor: colors.border },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.background },
  image: { width: '100%', backgroundColor: '#f0f0f0' },
  placeholder: { alignItems: 'center', justifyContent: 'center' },
  body: { padding: spacing.lg },
  name: { fontSize: 26, fontWeight: '700', color: colors.text, marginBottom: 6 },
  category: { fontSize: 14, color: colors.accent, marginBottom: 8 },
  price: { fontSize: 22, fontWeight: '700', marginBottom: 8 },
  stock: { fontSize: 14, color: colors.textMuted, marginBottom: spacing.md },
  description: { fontSize: 16, lineHeight: 24, color: colors.text },
  muted: { color: colors.textMuted },
});
