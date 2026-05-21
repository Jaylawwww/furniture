import React, { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { ProductCard } from '../components/ProductCard';
import { Screen, Input } from '../components/Screen';
import { fetchProducts } from '../api';
import { colors, spacing } from '../theme';

export default function HomeScreen({ navigation, route }) {
  const { width } = useWindowDimensions();
  const numColumns = width >= 640 ? 2 : 1;
  const categoryId = route.params?.categoryId;
  const categoryName = route.params?.categoryName;
  const [products, setProducts] = useState([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async (q = search) => {
    const data = await fetchProducts({
      q: q.trim() || undefined,
      category: categoryId,
    });
    setProducts(data.items || []);
  }, [search, categoryId]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      load().catch(() => setProducts([])).finally(() => setLoading(false));
    }, [load]),
  );

  const onRefresh = async () => {
    setRefreshing(true);
    try {
      await load();
    } finally {
      setRefreshing(false);
    }
  };

  const onSearch = async () => {
    setLoading(true);
    try {
      await load(search);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Screen
      title={categoryName ? categoryName : 'Shop'}
      subtitle={categoryName ? 'Products in this category.' : 'Browse our furniture collection.'}
      scroll={false}
    >
      <View style={styles.searchRow}>
        <View style={styles.searchField}>
          <Input
            label="Search"
            value={search}
            onChangeText={setSearch}
            placeholder="Search products…"
            onSubmitEditing={onSearch}
            returnKeyType="search"
          />
        </View>
        <Pressable onPress={onSearch} style={styles.searchGo} hitSlop={8}>
          <Text style={styles.searchGoText}>Go</Text>
        </Pressable>
      </View>
      {loading ? (
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.lg }} />
      ) : (
        <FlatList
          key={numColumns}
          numColumns={numColumns}
          data={products}
          keyExtractor={(item) => String(item.id)}
          columnWrapperStyle={numColumns > 1 ? styles.columnWrap : undefined}
          renderItem={({ item }) => {
            const card = (
              <ProductCard
                product={item}
                compact={numColumns > 1}
                onPress={() => navigation.navigate('ProductDetail', { id: item.id })}
              />
            );
            if (numColumns > 1) {
              return <View style={styles.gridCell}>{card}</View>;
            }
            return card;
          }}
          contentContainerStyle={styles.list}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          ListEmptyComponent={
            <Text style={styles.empty}>No products found.</Text>
          }
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  searchRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
  },
  searchField: { flex: 1, minWidth: 0 },
  searchGo: {
    paddingBottom: 14,
    paddingHorizontal: spacing.sm,
    justifyContent: 'center',
  },
  searchGoText: { fontWeight: '700', color: colors.primary, fontSize: 16 },
  columnWrap: { gap: spacing.sm },
  gridCell: { flex: 1, maxWidth: '50%' },
  list: { paddingHorizontal: spacing.lg, paddingBottom: spacing.xl },
  empty: { textAlign: 'center', color: colors.textMuted, marginTop: spacing.xl },
});
